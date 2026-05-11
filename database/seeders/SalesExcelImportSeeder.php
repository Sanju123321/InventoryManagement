<?php

namespace Database\Seeders;

use App\Support\SimpleXlsxReader;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Imports sales from the "LTD" sheet of the April 2026 sale book export.
 *
 * Each Excel row becomes one sales order (fully paid) with a single line item.
 * Required columns (row 4): Date, Account Name, State, Product Name, UOM, Qty., Rate, Amount.
 *
 * Set env SALE_IMPORT_XLSX to your .xlsx path, or change defaultPath() below.
 *
 * Run: php artisan db:seed --class=SalesExcelImportSeeder
 */
class SalesExcelImportSeeder extends Seeder
{
    private const COMPANY_ID = 5;

    private const STATUS = 'delivered';

    private const CREATED_BY_USER_ID = 2;

    private const APPROVED_BY_USER_ID = 2;

    private const SHEET_NAME = 'LTD';

    private const ITEM_INSERT_CHUNK = 500;

    public function run(): void
    {
        $path = $this->resolveExcelPath();
        if (! is_file($path)) {
            throw new RuntimeException("Missing Excel file: {$path}. Set SALE_IMPORT_XLSX or place the file at the default path.");
        }

        $rows = SimpleXlsxReader::readSheetByName($path, self::SHEET_NAME);
        $headerIndex = $this->findHeaderRowIndex($rows);
        if ($headerIndex === null) {
            throw new RuntimeException('Could not find header row (expected "Account Name" in column B).');
        }

        $customerMap = $this->loadCustomerNameMap(self::COMPANY_ID);
        $productMap = $this->loadProductNameMap(self::COMPANY_ID);

        $itemRows = [];
        $importedOrders = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $rows,
            $headerIndex,
            $customerMap,
            $productMap,
            &$itemRows,
            &$importedOrders,
            &$skipped
        ): void {
            for ($i = $headerIndex + 1; $i < count($rows); $i++) {
                $line = $rows[$i];
                if ($this->shouldSkipLineRow($line)) {
                    continue;
                }

                $accountRaw = isset($line[1]) ? (string) $line[1] : '';
                $productRaw = isset($line[3]) ? (string) $line[3] : '';
                $qtyRaw = $line[5] ?? null;
                $rateRaw = $line[6] ?? null;
                $amountRaw = $line[7] ?? null;
                $dateRaw = $line[0] ?? null;

                $cKey = self::normalizeNameForMatch($accountRaw);
                $pKey = self::normalizeNameForMatch($productRaw);

                $customerId = $customerMap[$cKey] ?? null;
                if ($customerId === null) {
                    $this->logSkip('customer_not_found', $line, $i + 1);
                    $skipped++;

                    continue;
                }

                $productId = $productMap[$pKey] ?? null;
                if ($productId === null) {
                    $this->logSkip('product_not_found', $line, $i + 1);
                    $skipped++;

                    continue;
                }

                $orderDate = $this->parseExcelDate($dateRaw);
                $ts = $orderDate->copy()->startOfDay();

                $amount = round((float) $amountRaw, 2);
                $rate = round((float) $rateRaw, 2);
                $qty = (int) round((float) $qtyRaw);

                if ($amount <= 0 || $qty <= 0) {
                    $this->logSkip('invalid_qty_or_amount', $line, $i + 1);
                    $skipped++;

                    continue;
                }

                $orderId = DB::table('sales_orders')->insertGetId([
                    'company_id' => self::COMPANY_ID,
                    'customer_id' => $customerId,
                    'total_amount' => $amount,
                    'paid_amount' => $amount,
                    'pending_amount' => 0,
                    'status' => self::STATUS,
                    'created_by' => self::CREATED_BY_USER_ID,
                    'approved_by' => self::APPROVED_BY_USER_ID,
                    'notes' => null,
                    'driver_name' => null,
                    'driver_whatsapp' => null,
                    'driver_vehicle' => null,
                    'delivery_date' => $ts->toDateString(),
                    'created_at' => $ts,
                    'updated_at' => $ts,
                ]);

                $importedOrders++;

                $itemRows[] = [
                    'sales_order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'price' => $rate,
                    'total' => $amount,
                    'created_at' => $ts,
                    'updated_at' => $ts,
                ];
            }

            foreach (array_chunk($itemRows, self::ITEM_INSERT_CHUNK) as $chunk) {
                DB::table('sales_order_items')->insert($chunk);
            }
        });

        $this->command?->info("SalesExcelImportSeeder: imported {$importedOrders} orders, skipped {$skipped} rows.");
    }

    public static function normalizeNameForMatch(string $name): string
    {
        $trimmed = trim($name);
        $collapsed = preg_replace('/\s+/', ' ', $trimmed) ?? $trimmed;

        return mb_strtoupper($collapsed, 'UTF-8');
    }

    /**
     * @param  list<list<string|int|float|bool|null>>  $rows
     */
    private function findHeaderRowIndex(array $rows): ?int
    {
        foreach ($rows as $idx => $row) {
            $b = isset($row[1]) ? trim((string) $row[1]) : '';
            $h0 = isset($row[0]) ? trim((string) $row[0]) : '';
            if ($b === 'Account Name' || ($h0 === 'Date' && str_contains($b, 'Account'))) {
                return $idx;
            }
        }

        return null;
    }

    /**
     * @param  list<string|int|float|bool|null>  $line
     */
    private function shouldSkipLineRow(array $line): bool
    {
        if ($line === []) {
            return true;
        }

        $h0 = isset($line[0]) ? trim((string) $line[0]) : '';
        if ($h0 === 'Date') {
            return true;
        }

        $account = isset($line[1]) ? trim((string) $line[1]) : '';
        $product = isset($line[3]) ? trim((string) $line[3]) : '';
        $amountCell = $line[7] ?? null;

        if ($account === '' || $product === '') {
            return true;
        }

        $uAccount = mb_strtoupper($account, 'UTF-8');
        $uProduct = mb_strtoupper($product, 'UTF-8');
        if ($uAccount === 'TOTAL' || str_contains($uProduct, 'TOTAL')) {
            return true;
        }

        if (! is_numeric($amountCell)) {
            return true;
        }

        return false;
    }

    /**
     * @param  int|float|string|null  $raw
     */
    private function parseExcelDate(mixed $raw): Carbon
    {
        if (is_int($raw) || is_float($raw)) {
            $serial = (float) $raw;
            $unix = (int) (($serial - 25569) * 86400);
            $dateStr = gmdate('Y-m-d', $unix);

            return Carbon::parse($dateStr, config('app.timezone'))->startOfDay();
        }

        $s = trim((string) $raw);
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $s);
            } catch (\Throwable) {
            }
        }

        throw new RuntimeException("Unparseable date: {$s}");
    }

    private function resolveExcelPath(): string
    {
        $fromEnv = env('SALE_IMPORT_XLSX');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        return 'C:/Users/sanju/Downloads/SALE APR 2026 - Copy.xlsx';
    }

    /**
     * @return array<string, int>
     */
    private function loadCustomerNameMap(int $companyId): array
    {
        $map = [];
        $rows = DB::table('customers')
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->get();
        foreach ($rows as $r) {
            $map[self::normalizeNameForMatch((string) $r->name)] = (int) $r->id;
        }

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function loadProductNameMap(int $companyId): array
    {
        $map = [];
        $rows = DB::table('products')
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->get();
        foreach ($rows as $r) {
            $map[self::normalizeNameForMatch((string) $r->name)] = (int) $r->id;
        }

        return $map;
    }

    /**
     * @param  list<string|int|float|bool|null>  $line
     */
    private function logSkip(string $reason, array $line, int $sheetRowNumber): void
    {
        Log::warning('SalesExcelImportSeeder skipped row', [
            'reason' => $reason,
            'sheet_row' => $sheetRowNumber,
            'account' => $line[1] ?? null,
            'product' => $line[3] ?? null,
        ]);
    }
}
