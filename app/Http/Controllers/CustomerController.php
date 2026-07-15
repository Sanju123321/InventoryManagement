<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\SalesOrder;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $query = Customer::where('company_id', $companyId)->with('creator');

        // Sales admins see only their own customers
        if (auth()->user()->role === 'sales_admin') {
            $query->where('created_by', auth()->id());
        }
        // Admin and superadmin see all customers

        $customers = $query->latest()->paginate(15);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customers', 'name')
                    ->where('company_id', $companyId),
            ],
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'google_location' => 'nullable|string|max:2000',
            'authorized_person' => 'nullable|string|max:255',
            'contact_details' => 'required|digits:10',
            'gst_number' => 'required|string|max:20',
            'md_details' => 'nullable|string|max:2000',
        ]);

        Customer::create([
            'company_id' => $companyId,
            'created_by' => auth()->id(),
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'google_location' => Customer::normalizeGoogleLocation($request->google_location),
            'authorized_person' => $request->authorized_person,
            'contact_details' => $request->contact_details,
            'gst_number' => $request->gst_number,
            'md_details' => $request->md_details,
        ]);

        ActivityLogService::log('customer.created', "Customer '{$request->name}' added.");
        return redirect('/customers')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $this->authorizeCustomerAccess($customer);

        // All orders chronologically (oldest first for ledger)
        $orders = $customer->salesOrders()->with('payments')->orderBy('created_at')->get();

        // Customer-level lump-sum payments
        $customerPayments = $customer->customerPayments()->orderBy('payment_date')->orderBy('id')->get();

        // Build ledger entries — merge orders (debit) + all payments (credit)
        $entries = collect();

        foreach ($orders as $order) {
            $entries->push([
                'date'        => $order->created_at->toDate(),
                'sort_key'    => $order->created_at->timestamp . '_order_' . str_pad($order->id, 10, '0', STR_PAD_LEFT),
                'type'        => 'order',
                'description' => 'Sales Order #' . $order->id . ' — ' . ucfirst($order->status),
                'debit'       => $order->total_amount,
                'credit'      => 0,
                'link'        => url('/sales/orders/' . $order->id),
                'method'      => null,
                'ref'         => null,
            ]);

            // Per-order payments already recorded on individual orders
            foreach ($order->payments as $px) {
                $entries->push([
                    'date'        => $px->payment_date,
                    'sort_key'    => $px->payment_date->timestamp . '_opay_' . str_pad($px->id, 10, '0', STR_PAD_LEFT),
                    'type'        => 'payment',
                    'description' => 'Payment against Order #' . $order->id,
                    'debit'       => 0,
                    'credit'      => $px->amount,
                    'link'        => url('/sales/orders/' . $order->id),
                    'method'      => $px->payment_method,
                    'ref'         => null,
                ]);
            }
        }

        // Customer-level (lump-sum) payments
        foreach ($customerPayments as $cp) {
            $entries->push([
                'date'        => $cp->payment_date,
                'sort_key'    => $cp->payment_date->timestamp . '_cpay_' . str_pad($cp->id, 10, '0', STR_PAD_LEFT),
                'type'        => 'customer_payment',
                'description' => 'Payment received' . ($cp->notes ? ' — ' . $cp->notes : ''),
                'debit'       => 0,
                'credit'      => $cp->amount,
                'link'        => null,
                'method'      => $cp->payment_method,
                'ref'         => $cp->reference,
            ]);
        }

        // Sort by date then by sort_key for stable ordering
        $ledger = $entries->sortBy('sort_key')->values();

        // Compute running balance
        $balance = 0;
        $ledger = $ledger->map(function ($e) use (&$balance) {
            $balance += $e['debit'] - $e['credit'];
            $e['balance'] = $balance;
            return $e;
        });

        $totalPurchase = $orders->sum('total_amount');
        // Total paid = sum of per-order paid amounts + customer lump-sum payments
        // But since customer payments are FIFO-allocated into orders, avoid double counting.
        // Use total_purchase - total_pending as the authoritative "total paid".
        $totalPending  = $orders->sum('pending_amount');
        $totalPaid     = $totalPurchase - $totalPending;

        return view('customers.show', compact('customer', 'orders', 'ledger', 'totalPurchase', 'totalPaid', 'totalPending'));
    }

    public function exportLedger(Customer $customer)
    {
        $this->authorizeCustomerAccess($customer);

        $orders = $customer->salesOrders()->with('payments')->orderBy('created_at')->get();
        $customerPayments = $customer->customerPayments()->orderBy('payment_date')->orderBy('id')->get();

        $entries = collect();

        foreach ($orders as $order) {
            $entries->push([
                'date'        => $order->created_at->format('d M Y'),
                'description' => 'Sales Order #' . $order->id . ' — ' . ucfirst($order->status),
                'debit'       => $order->total_amount,
                'credit'      => 0,
                'method'      => '',
                'ref'         => '',
            ]);
            foreach ($order->payments as $px) {
                $entries->push([
                    'date'        => $px->payment_date->format('d M Y'),
                    'description' => 'Payment against Order #' . $order->id,
                    'debit'       => 0,
                    'credit'      => $px->amount,
                    'method'      => ucfirst(str_replace('_', ' ', $px->payment_method)),
                    'ref'         => '',
                ]);
            }
        }

        foreach ($customerPayments as $cp) {
            $entries->push([
                'date'        => $cp->payment_date->format('d M Y'),
                'description' => 'Payment received' . ($cp->notes ? ' — ' . $cp->notes : ''),
                'debit'       => 0,
                'credit'      => $cp->amount,
                'method'      => ucfirst(str_replace('_', ' ', $cp->payment_method)),
                'ref'         => $cp->reference ?? '',
            ]);
        }

        $filename = 'ledger_' . str_replace(' ', '_', $customer->name) . '_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customer, $entries) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Customer: ' . $customer->name]);
            fputcsv($handle, ['Phone: ' . ($customer->phone ?? '-'), 'GST: ' . ($customer->gst_number ?? '-')]);
            fputcsv($handle, []);
            fputcsv($handle, ['Date', 'Description', 'Method', 'Ref', 'Order Amount (₹)', 'Credited (₹)', 'Balance Due (₹)']);

            $balance = 0;
            foreach ($entries as $e) {
                $balance += $e['debit'] - $e['credit'];
                fputcsv($handle, [
                    $e['date'],
                    $e['description'],
                    $e['method'],
                    $e['ref'],
                    $e['debit'] > 0 ? number_format($e['debit'], 2) : '',
                    $e['credit'] > 0 ? number_format($e['credit'], 2) : '',
                    number_format($balance, 2),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function storePayment(Request $request, Customer $customer)
    {
        $this->authorizeCustomerAccess($customer);

        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,upi,cheque,other',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ]);

        $entered = (float) $request->amount;

        // Save the customer-level payment record
        CustomerPayment::create([
            'company_id'     => auth()->user()->company_id,
            'customer_id'    => $customer->id,
            'amount'         => $entered,
            'payment_date'   => $request->payment_date,
            'payment_method' => $request->payment_method,
            'reference'      => $request->reference,
            'notes'          => $request->notes,
        ]);

        // FIFO auto-allocation: oldest approved/delivered orders first
        $pendingOrders = SalesOrder::where('customer_id', $customer->id)
            ->whereIn('status', ['approved', 'dispatched', 'paid'])
            ->where('pending_amount', '>', 0)
            ->orderBy('created_at')
            ->get();

        $remaining = $entered;
        foreach ($pendingOrders as $order) {
            if ($remaining <= 0) break;

            $allocate   = min($remaining, (float) $order->pending_amount);
            $newPaid    = (float) $order->paid_amount + $allocate;
            $newPending = (float) $order->total_amount - $newPaid;

            $order->update([
                'paid_amount'    => $newPaid,
                'pending_amount' => $newPending,
            ]);

            $remaining -= $allocate;
        }

        ActivityLogService::log('customer.payment', "Payment of ₹{$entered} recorded for '{$customer->name}'.");

        return back()->with('success', '₹' . number_format($entered, 2) . ' recorded and auto-allocated to pending orders.');
    }

    public function edit(Customer $customer)
    {
        $this->authorizeCustomerAccess($customer);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCustomerAccess($customer);

        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customers', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($customer->id),
            ],
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'google_location' => 'nullable|string|max:2000',
            'authorized_person' => 'nullable|string|max:255',
            'contact_details' => 'required|digits:10',
            'gst_number' => 'required|string|max:20',
            'md_details' => 'nullable|string|max:2000',
        ]);

        $customer->update([
            ...$request->only('name', 'phone', 'email', 'address', 'authorized_person', 'contact_details', 'gst_number', 'md_details'),
            'google_location' => Customer::normalizeGoogleLocation($request->google_location),
        ]);

        ActivityLogService::log('customer.updated', "Customer '{$customer->name}' updated.");
        return redirect('/customers')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorizeCustomerAccess($customer);
        $name = $customer->name;
        $customer->delete();
        ActivityLogService::log('customer.deleted', "Customer '{$name}' deleted.");
        return redirect('/customers')->with('success', 'Customer deleted successfully.');
    }

    /**
     * Authorize customer access based on role
     * Sales admins can only access their own customers
     * Admin and superadmin can access all customers
     */
    private function authorizeCustomerAccess(Customer $customer)
    {
        abort_unless($customer->company_id === auth()->user()->company_id, 403);

        // Sales admins can only access their own customers
        if (auth()->user()->role === 'sales_admin') {
            abort_unless($customer->created_by === auth()->id(), 403);
        }
        // Admin and superadmin can access all customers in their company
    }

    public function export()
    {
        $companyId = auth()->user()->company_id;
        $query = Customer::where('company_id', $companyId)->with('creator');

        if (auth()->user()->role === 'sales_admin') {
            $query->where('created_by', auth()->id());
        }

        $customers = $query->latest()->get();

        $filename = 'customers_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Name', 'Phone', 'Email', 'Address', 'Contact Number', 'GST Number', 'Authorized Person', 'MD Details', 'Added By', 'Created At']);

            foreach ($customers as $i => $c) {
                fputcsv($handle, [
                    $i + 1,
                    $c->name,
                    $c->phone,
                    $c->email,
                    $c->address,
                    $c->contact_details,
                    $c->gst_number,
                    $c->authorized_person,
                    $c->md_details,
                    $c->creator?->name ?? '-',
                    $c->created_at->format('d M Y'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importTemplate()
    {
        $filename = 'customers_import_template.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $user = auth()->user();

        $callback = function () use ($user) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'company_id',
                'created_by',
                'name',
                'phone',
                'email',
                'address',
                'google_location',
                'state',
                'authorized_person',
                'contact_details',
                'gst_number',
                'md_details',
            ]);
            fputcsv($handle, [
                $user->company_id,
                $user->id,
                'SAMPLE TRADERS',
                '9876543210',
                'sample@example.com',
                'Panipat, Haryana',
                '',
                'Haryana',
                '',
                '9876543210',
                '06AAAAA0000A1Z5',
                '',
            ]);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'max:5120'],
        ]);

        $ext = strtolower($request->file('csv_file')->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['csv', 'txt'], true)) {
            return back()->with('error', 'Please upload a .csv file (not Excel .xlsx). Use Download template.');
        }

        $authUser = auth()->user();
        $defaultCompanyId = (int) $authUser->company_id;
        $path = $request->file('csv_file')->getRealPath();

        $rows = $this->readCustomerCsvRows($path);
        if ($rows === null) {
            return back()->with('error', 'Unable to read the uploaded CSV file.');
        }
        if (count($rows) === 0) {
            return back()->with('error', 'The CSV file is empty.');
        }

        $headerRow = array_shift($rows);
        $map = $this->mapCustomerImportHeaders($headerRow);

        if (! isset($map['name'])) {
            $found = implode(', ', array_map(fn ($h) => '"' . $h . '"', $headerRow));

            return back()->with(
                'error',
                'CSV must include a name column. Found headers: ' . ($found !== '' ? $found : '(none)') . '. Download the template for the correct format.'
            );
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        foreach ($rows as $row) {
            $rowNum++;

            if ($this->csvRowIsEmpty($row)) {
                continue;
            }

            $name = trim((string) ($row[$map['name']] ?? ''));
            if ($name === '') {
                $skipped++;
                $errors[] = "Row {$rowNum}: missing name.";
                continue;
            }

            $rowCompanyId = isset($map['company_id'])
                ? (int) trim((string) ($row[$map['company_id']] ?? ''))
                : 0;
            if ($rowCompanyId <= 0) {
                $rowCompanyId = $defaultCompanyId;
            }

            // Non-superadmin can only import into their own company
            if (! $authUser->isSuperAdmin() && $rowCompanyId !== $defaultCompanyId) {
                $skipped++;
                $errors[] = "Row {$rowNum}: company_id {$rowCompanyId} is not allowed.";
                continue;
            }

            if (! \App\Models\Company::whereKey($rowCompanyId)->exists()) {
                $skipped++;
                $errors[] = "Row {$rowNum}: company_id {$rowCompanyId} does not exist.";
                continue;
            }

            $createdBy = isset($map['created_by'])
                ? (int) trim((string) ($row[$map['created_by']] ?? ''))
                : 0;
            if ($createdBy > 0) {
                $creatorOk = \App\Models\User::whereKey($createdBy)
                    ->when(! $authUser->isSuperAdmin(), fn ($q) => $q->where('company_id', $rowCompanyId))
                    ->exists();
                if (! $creatorOk) {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: created_by {$createdBy} is invalid for company_id {$rowCompanyId}.";
                    continue;
                }
            } else {
                $createdBy = (int) $authUser->id;
            }

            if ($authUser->role === 'sales_admin' && $createdBy !== (int) $authUser->id) {
                $skipped++;
                $errors[] = "Row {$rowNum}: sales admin can only import with their own created_by.";
                continue;
            }

            $phone = trim((string) ($row[$map['phone'] ?? -1] ?? ''));
            $email = trim((string) ($row[$map['email'] ?? -1] ?? ''));
            $address = trim((string) ($row[$map['address'] ?? -1] ?? ''));
            $googleLocation = trim((string) ($row[$map['google_location'] ?? -1] ?? ''));
            $state = trim((string) ($row[$map['state'] ?? -1] ?? ''));
            $contact = trim((string) ($row[$map['contact_details'] ?? -1] ?? ''));
            $gst = trim((string) ($row[$map['gst_number'] ?? -1] ?? ''));
            $authorized = trim((string) ($row[$map['authorized_person'] ?? -1] ?? ''));
            $mdDetails = trim((string) ($row[$map['md_details'] ?? -1] ?? ''));

            if ($contact === '' && $phone !== '') {
                $contact = $phone;
            }
            $contactDigits = preg_replace('/\D/', '', $contact);
            if (strlen($contactDigits) >= 10) {
                $contactDigits = substr($contactDigits, -10);
            } elseif ($contactDigits === '') {
                $contactDigits = '0000000000';
            }

            if ($gst === '') {
                $gst = 'N/A';
            }

            $payload = [
                'company_id' => $rowCompanyId,
                'created_by' => $createdBy,
                'phone' => $phone !== '' ? $phone : null,
                'email' => $email !== '' ? $email : null,
                'address' => $address !== '' ? $address : null,
                'google_location' => $googleLocation !== '' ? Customer::normalizeGoogleLocation($googleLocation) : null,
                'state' => $state !== '' ? $state : null,
                'contact_details' => $contactDigits,
                'gst_number' => $gst,
                'authorized_person' => $authorized !== '' ? $authorized : null,
                'md_details' => $mdDetails !== '' ? $mdDetails : null,
            ];

            $existing = Customer::where('company_id', $rowCompanyId)
                ->where('name', $name)
                ->when($gst !== 'N/A', fn ($q) => $q->where('gst_number', $gst))
                ->first();

            if ($existing) {
                if ($authUser->role === 'sales_admin' && (int) $existing->created_by !== (int) $authUser->id) {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: customer '{$name}' belongs to another user.";
                    continue;
                }
                $existing->update($payload);
                $updated++;
            } else {
                Customer::create(array_merge($payload, [
                    'name' => $name,
                ]));
                $created++;
            }
        }

        ActivityLogService::log(
            'customer.imported',
            "Customer CSV imported. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}."
        );

        $message = "Import complete: {$created} created, {$updated} updated";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }
        $message .= '.';

        if (! empty($errors)) {
            $message .= ' Issues: ' . implode(' ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= ' …';
            }
        }

        return redirect('/customers')->with('success', $message);
    }

    /**
     * @return list<list<string>>|null
     */
    private function readCustomerCsvRows(string $path): ?array
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        // UTF-16 (Excel sometimes exports this)
        if (str_starts_with($raw, "\xFF\xFE") || str_starts_with($raw, "\xFE\xFF")) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
        } elseif (! mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $delimiter = $this->detectCsvDelimiter($raw);
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return null;
        }

        fwrite($handle, $raw);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(fn ($cell) => trim((string) $cell), $row);
        }
        fclose($handle);

        return $rows;
    }

    private function detectCsvDelimiter(string $raw): string
    {
        $firstLine = strtok($raw, "\n") ?: '';
        $candidates = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];

        foreach ($candidates as $delimiter => $_) {
            $candidates[$delimiter] = substr_count($firstLine, $delimiter);
        }

        arsort($candidates);
        $best = array_key_first($candidates);

        return ($candidates[$best] ?? 0) > 0 ? $best : ',';
    }

    private function mapCustomerImportHeaders(array $headerRow): array
    {
        $aliases = [
            'company_id' => ['company_id', 'companyid', 'company'],
            'created_by' => ['created_by', 'createdby', 'created by'],
            'name' => ['name', 'party_name', 'party name', 'customer_name', 'customer name', 'customer'],
            'phone' => ['phone', 'mobile'],
            'email' => ['email'],
            'address' => ['address', 'city'],
            'google_location' => ['google_location', 'google location'],
            'state' => ['state'],
            'authorized_person' => ['authorized_person', 'authorized person', 'agent'],
            'contact_details' => ['contact_details', 'contact details', 'contact_number', 'contact number', 'contact_no', 'contact no', 'contact no.'],
            'gst_number' => ['gst_number', 'gst number', 'gst_no', 'gst no', 'gst no.', 'gst'],
            'md_details' => ['md_details', 'md details', 'md'],
        ];

        $map = [];
        foreach ($headerRow as $index => $label) {
            $key = $this->normalizeCsvHeader((string) $label);
            if ($key === '') {
                continue;
            }

            foreach ($aliases as $field => $names) {
                $normalizedNames = array_map(fn ($n) => $this->normalizeCsvHeader($n), $names);
                if (in_array($key, $normalizedNames, true) && ! isset($map[$field])) {
                    $map[$field] = $index;
                }
            }
        }

        return $map;
    }

    private function normalizeCsvHeader(string $label): string
    {
        $label = preg_replace('/^\xEF\xBB\xBF/', '', $label) ?? $label;
        $label = strtolower(trim($label));
        $label = str_replace(['"', "'"], '', $label);
        $label = preg_replace('/[\s\-]+/', '_', $label) ?? $label;
        $label = preg_replace('/_+/', '_', $label) ?? $label;

        return trim($label, " \t\n\r\0\x0B._");
    }

    private function csvRowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
