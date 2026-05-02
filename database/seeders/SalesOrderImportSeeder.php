<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesOrderImportSeeder extends Seeder
{
    /**
     * Target company for imported orders (matches CustomerSeeder / products import).
     */
    private const COMPANY_ID = 5;

    /**
     * User id for created_by (must exist in users).
     */
    private const CREATED_BY_USER_ID = 2;

    /**
     * Rows per chunk for batch insert into sales_order_items.
     */
    private const ITEM_INSERT_CHUNK = 500;

    public function run(): void
    {
        $grouped = $this->mergeGroupedRows($this->salesData());

        $customerMap = $this->loadCustomerNameMap(self::COMPANY_ID);
        $productMap = $this->loadProductNameMap(self::COMPANY_ID);

        $prepared = [];
        foreach ($grouped as $row) {
            $dateStr = $row['date'];
            $customerRaw = $row['customer'];
            $customerKey = self::normalizeNameForMatch($customerRaw);

            $customerId = $customerMap[$customerKey] ?? null;
            if ($customerId === null) {
                $this->logSkipped('customer_not_found', [
                    'date' => $dateStr,
                    'customer' => $customerRaw,
                ]);

                continue;
            }

            $parsedAt = $this->parseOrderDate($dateStr);

            $validItems = [];
            foreach ($row['items'] as $item) {
                $productRaw = $item['product'];
                $productKey = self::normalizeNameForMatch($productRaw);
                $productId = $productMap[$productKey] ?? null;

                if ($productId === null) {
                    $this->logSkipped('product_not_found', [
                        'date' => $dateStr,
                        'customer' => $customerRaw,
                        'product' => $productRaw,
                    ]);

                    continue;
                }

                $qty = (int) $item['qty'];
                $rate = (float) $item['rate'];
                $lineTotal = round($qty * $rate, 2);

                $validItems[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'price' => round($rate, 2),
                    'total' => $lineTotal,
                ];
            }

            if ($validItems === []) {
                continue;
            }

            $prepared[] = [
                'parsed_at' => $parsedAt,
                'customer_id' => $customerId,
                'items' => $validItems,
            ];
        }

        $orderCount = count($prepared);
        if ($orderCount === 0) {
            $this->command?->info('SalesOrderImportSeeder: nothing to import.');

            return;
        }

        $itemRows = [];

        DB::transaction(function () use ($prepared, &$itemRows): void {
            foreach ($prepared as $group) {
                $totalAmount = 0.0;
                foreach ($group['items'] as $line) {
                    $totalAmount += $line['total'];
                }
                $totalAmount = round($totalAmount, 2);

                $ts = $group['parsed_at'];

                $orderId = DB::table('sales_orders')->insertGetId([
                    'company_id' => self::COMPANY_ID,
                    'customer_id' => $group['customer_id'],
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'pending_amount' => $totalAmount,
                    'status' => 'delivered',
                    'created_by' => self::CREATED_BY_USER_ID,
                    'approved_by' => null,
                    'created_at' => $ts,
                    'updated_at' => $ts,
                ]);

                foreach ($group['items'] as $line) {
                    $itemRows[] = [
                        'sales_order_id' => $orderId,
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'total' => $line['total'],
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ];
                }
            }

            foreach (array_chunk($itemRows, self::ITEM_INSERT_CHUNK) as $chunk) {
                DB::table('sales_order_items')->insert($chunk);
            }
        });

        $this->command?->info(sprintf(
            'SalesOrderImportSeeder: imported %d orders and %d line items.',
            $orderCount,
            count($itemRows)
        ));
    }

    /**
     * Trim, collapse internal whitespace, uppercase — for reliable name lookups.
     */
    public static function normalizeNameForMatch(string $name): string
    {
        $trimmed = trim($name);
        $collapsed = preg_replace('/\s+/', ' ', $trimmed) ?? $trimmed;

        return mb_strtoupper($collapsed, 'UTF-8');
    }

    /**
     * Convert flat rows (date, customer, product, qty, rate) into grouped payloads.
     *
     * @param  list<array{date: string, customer: string, product: string, qty: int|float|string, rate: int|float|string}>  $flatRows
     * @return list<array{date: string, customer: string, items: list<array{product: string, qty: float|int, rate: float|int}>}>
     */
    public static function groupedFromFlatRows(array $flatRows): array
    {
        $groups = [];

        foreach ($flatRows as $row) {
            $date = $row['date'];
            $customer = $row['customer'];
            $key = $date.'|'.self::normalizeNameForMatch($customer);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'date' => $date,
                    'customer' => $customer,
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = [
                'product' => $row['product'],
                'qty' => $row['qty'],
                'rate' => $row['rate'],
            ];
        }

        return array_values($groups);
    }

    /**
     * @param  list<array{date: string, customer: string, items: list<array{product: string, qty: int|float, rate: int|float}>}>  $rows
     * @return list<array{date: string, customer: string, items: list<array{product: string, qty: float|int, rate: float|int}>}>
     */
    private function mergeGroupedRows(array $rows): array
    {
        $merged = [];

        foreach ($rows as $row) {
            $key = $row['date'].'|'.self::normalizeNameForMatch($row['customer']);
            if (! isset($merged[$key])) {
                $merged[$key] = [
                    'date' => $row['date'],
                    'customer' => $row['customer'],
                    'items' => [],
                ];
            }
            foreach ($row['items'] as $item) {
                $merged[$key]['items'][] = $item;
            }
        }

        return array_values($merged);
    }

    private function parseOrderDate(string $dmy): Carbon
    {
        return Carbon::createFromFormat('d-m-Y', $dmy)->startOfDay();
    }

    /**
     * @return array<string, int> normalized name => customer id
     */
    private function loadCustomerNameMap(int $companyId): array
    {
        $map = [];
        $rows = DB::table('customers')
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->get();

        foreach ($rows as $r) {
            $key = self::normalizeNameForMatch((string) $r->name);
            $map[$key] = (int) $r->id;
        }

        return $map;
    }

    /**
     * @return array<string, int> normalized name => product id
     */
    private function loadProductNameMap(int $companyId): array
    {
        $map = [];
        $rows = DB::table('products')
            ->where('company_id', $companyId)
            ->select('id', 'name')
            ->get();

        foreach ($rows as $r) {
            $key = self::normalizeNameForMatch((string) $r->name);
            $map[$key] = (int) $r->id;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logSkipped(string $reason, array $context): void
    {
        Log::warning('SalesOrderImportSeeder skipped row', array_merge(['reason' => $reason], $context));
    }

    /**
     * Example grouped dataset (date d-m-Y). Replace or override in a custom seeder
     * that extends this class, or paste your full list here.
     *
     * Flat format can be converted with self::groupedFromFlatRows([...]).
     *
     * @return list<array{date: string, customer: string, items: list<array{product: string, qty: int|float, rate: int|float}>}>
     */
    protected function salesData(): array
    {
        return [
            [
                'date' => '01-04-2026',
                'customer' => 'J C BUDHIRAJA HOSIERY',
                'items' => [
                    ['product' => 'K-knit N/w 68', 'qty' => 5, 'rate' => 140],
                ],
            ],
            [
                'date' => '01-04-2026',
                'customer' => 'NEVA GARMENTS LTD',
                'items' => [
                    ['product' => 'K-knit Wash 22/32', 'qty' => 50, 'rate' => 190],
                ],
            ],
            [
                'date' => '01-04-2026',
                'customer' => 'AGGARWAL ENTERPRISES',
                'items' => [
                    ['product' => 'Kemtex Gear 150', 'qty' => 210, 'rate' => 115],
                ],
            ],
            [
                'date' => '02-04-2026',
                'customer' => 'HINGLAJ PROCESSOR',
                'items' => [
                    ['product' => 'Kemtex Xjl Chain Oil', 'qty' => 100, 'rate' => 540],
                ],
            ],
            [
                'date' => '02-04-2026',
                'customer' => 'J C BUDHIRAJA HOSIERY',
                'items' => [
                    ['product' => 'K-knit N/w 68', 'qty' => 26, 'rate' => 140],
                ],
            ],
            [
                'date' => '02-04-2026',
                'customer' => 'SP INDUSTRIES',
                'items' => [
                    ['product' => 'K-knit N/w 68', 'qty' => 26, 'rate' => 135],
                ],
            ],
            [
                'date' => '02-04-2026',
                'customer' => 'AARON FABRICS',
                'items' => [
                    ['product' => 'K-knit Wash 22/32', 'qty' => 210, 'rate' => 153],
                ],
            ],
            [
                'date' => '02-04-2026',
                'customer' => 'AKASH KNITWEARS UNIT 2',
                'items' => [
                    ['product' => 'K-knit Wash 22/32', 'qty' => 210, 'rate' => 170],
                ],
            ],
            [
                'date' => '02-04-2026',
                'customer' => 'JAY BEE WOOLLEN MILLS',
                'items' => [
                    ['product' => 'K-knit Wash 22/32', 'qty' => 420, 'rate' => 148],
                ],
            ],
            [
                'date' => '02-04-2026',
                'customer' => 'LUCKY FABRICS',
                'items' => [
                    ['product' => 'K-knit Wash 22/32', 'qty' => 26, 'rate' => 160],
                ],
            ],
        ];
    }
}
