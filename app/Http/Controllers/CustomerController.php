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
            ->whereIn('status', ['approved', 'delivered'])
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
                'status'         => $newPending <= 0 ? 'paid' : $order->status,
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
            'authorized_person' => 'nullable|string|max:255',
            'contact_details' => 'required|digits:10',
            'gst_number' => 'required|string|max:20',
            'md_details' => 'nullable|string|max:2000',
        ]);

        $customer->update($request->only('name', 'phone', 'email', 'address', 'authorized_person', 'contact_details', 'gst_number', 'md_details'));

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
}
