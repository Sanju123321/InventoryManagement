<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Firm;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\SalesOrder;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $query = SalesOrder::where('company_id', $companyId)->with('customer', 'firm', 'creator');

        // Sales admins see only their own orders
        if (auth()->user()->role === 'sales_admin') {
            $query->where('created_by', auth()->id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('firm_id')) {
            $query->where('firm_id', $request->firm_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $orders = $query->latest()->paginate(15)->appends($request->query());

        // Sales admins see only their own customers, Admin/superadmin see all
        $customersQuery = Customer::where('company_id', $companyId);
        if (auth()->user()->role === 'sales_admin') {
            $customersQuery->where('created_by', auth()->id());
        }
        $customers = $customersQuery->orderBy('name')->get();
        $firms = Firm::where('company_id', $companyId)->orderBy('name')->get();

        return view('sales.orders.index', compact('orders', 'customers', 'firms'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;
        
        // Sales admins see only their own customers, Admin/superadmin see all
        $customersQuery = Customer::where('company_id', $companyId);
        if (auth()->user()->role === 'sales_admin') {
            $customersQuery->where('created_by', auth()->id());
        }
        $customers = $customersQuery->orderBy('name')->get();
        $firms = $this->activeFirmsForCompany($companyId);

        $products = Product::where('company_id', $companyId)->orderBy('name')->get();
        $productCosts = ProductCost::where('company_id', $companyId)->pluck('selling_price', 'product_id');

        return view('sales.orders.create', compact('customers', 'firms', 'products', 'productCosts'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $isAdmin = auth()->user()->role === 'admin';

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'firm_id' => $this->firmValidationRule($companyId),
            'gst_rate' => 'required|in:0,5,18',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|gt:0',
        ]);

        // Validate that the customer belongs to the company
        $customer = Customer::findOrFail($request->customer_id);
        abort_unless($customer->company_id === $companyId, 403);

        // Sales admins can only create orders for their own customers
        if (auth()->user()->role === 'sales_admin') {
            abort_unless($customer->created_by === auth()->id(), 403);
        }

        $order = null;
        $totals = $this->calculateOrderTotals(
            $request->items,
            (int) $request->gst_rate,
            (float) ($request->discount_amount ?? 0)
        );

        DB::transaction(function () use ($request, $companyId, $isAdmin, $totals, &$order) {
            $itemsData = [];

            foreach ($request->items as $item) {
                $lineTotal = round($item['quantity'] * $item['price'], 2);
                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $lineTotal,
                ];
            }

            $order = SalesOrder::create([
                'company_id' => $companyId,
                'customer_id' => $request->customer_id,
                'firm_id' => $request->firm_id,
                'subtotal' => $totals['subtotal'],
                'gst_rate' => $totals['gst_rate'],
                'gst_amount' => $totals['gst_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['grand_total'],
                'paid_amount' => 0,
                'pending_amount' => $totals['grand_total'],
                'status' => $isAdmin ? 'approved' : 'pending',
                'approved_by' => $isAdmin ? auth()->id() : null,
                'created_by' => auth()->id(),
                'notes' => $request->notes,
            ]);

            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }
        });

        ActivityLogService::log('sales.created', "Sales order #{$order->id} created for customer ID {$order->customer_id}. Total: ₹" . number_format($order->total_amount, 2) . ".");

        return redirect('/sales/orders')->with('success', $isAdmin
            ? 'Sales order created and approved successfully.'
            : 'Sales order created successfully.');
    }

    public function edit(SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        abort_if(auth()->user()->role === 'sales_admin', 403);

        $companyId = auth()->user()->company_id;
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();
        $firms = $this->firmsForOrderForm($companyId, $order->firm_id);
        $products = Product::where('company_id', $companyId)->orderBy('name')->get();
        $productCosts = ProductCost::where('company_id', $companyId)->pluck('selling_price', 'product_id');

        $order->load('items.product', 'customer', 'firm');

        return view('sales.orders.edit', compact('order', 'customers', 'firms', 'products', 'productCosts'));
    }

    public function update(Request $request, SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        abort_if(auth()->user()->role === 'sales_admin', 403);

        $companyId = auth()->user()->company_id;

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'firm_id' => $this->firmValidationRule($companyId, $order->firm_id),
            'gst_rate' => 'required|in:0,5,18',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|gt:0',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        abort_unless($customer->company_id === $companyId, 403);

        $totals = $this->calculateOrderTotals(
            $request->items,
            (int) $request->gst_rate,
            (float) ($request->discount_amount ?? 0)
        );

        DB::transaction(function () use ($request, $order, $totals) {
            $itemsData = [];

            foreach ($request->items as $item) {
                $lineTotal = round($item['quantity'] * $item['price'], 2);
                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $lineTotal,
                ];
            }

            $order->update([
                'customer_id' => $request->customer_id,
                'firm_id' => $request->firm_id,
                'subtotal' => $totals['subtotal'],
                'gst_rate' => $totals['gst_rate'],
                'gst_amount' => $totals['gst_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['grand_total'],
                'pending_amount' => max(0, $totals['grand_total'] - $order->paid_amount),
                'notes' => $request->notes,
            ]);

            $order->items()->delete();

            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }
        });

        ActivityLogService::log('sales.updated', "Sales order #{$order->id} updated.");

        return redirect('/sales/orders/' . $order->id)->with('success', 'Sales order updated successfully.');
    }

    public function show(SalesOrder $order)
    {
        $this->authorizeOrderAccess($order);
        $order->load('customer', 'firm', 'items.product', 'payments', 'creator', 'approver', 'company');
        $companyId = auth()->user()->company_id;
        $productCosts = ProductCost::where('company_id', $companyId)
            ->pluck('selling_price', 'product_id');

        return view('sales.orders.show', compact('order', 'productCosts'));
    }

    public function print(SalesOrder $order)
    {
        $this->authorizeOrderAccess($order);
        $order->load('customer', 'items.product', 'company', 'creator');

        return view('sales.orders.print', compact('order'));
    }

    public function updateNotes(Request $request, SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        abort_if(auth()->user()->role === 'sales_admin', 403);
        $request->validate(['notes' => 'nullable|string|max:2000']);
        $order->update(['notes' => $request->notes]);
        return back()->with('success', 'Notes updated.');
    }

    public function updateDriver(Request $request, SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        abort_if(auth()->user()->role === 'sales_admin', 403);
        $request->validate([
            'driver_name'     => 'required|string|max:100',
            'driver_whatsapp' => 'required|digits_between:10,15',
            'driver_vehicle'  => 'nullable|string|max:50',
            'delivery_date'   => 'required|date|after_or_equal:today',
        ]);
        $order->update($request->only('driver_name', 'driver_whatsapp', 'driver_vehicle', 'delivery_date'));
        ActivityLogService::log('sales.driver_assigned', "Driver '{$request->driver_name}' assigned to order #{$order->id}.");
        return back()->with('success', 'Driver assigned successfully.');
    }

    /**
     * List previous orders for a customer (for "Load previous items" picker).
     */
    public function previousOrders(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $request->validate(['customer_id' => 'required|exists:customers,id']);

        $customer = Customer::where('company_id', $companyId)->findOrFail($request->customer_id);

        if (auth()->user()->role === 'sales_admin') {
            abort_unless($customer->created_by === auth()->id(), 403);
        }

        $query = SalesOrder::where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->with(['items.product'])
            ->latest();

        if (auth()->user()->role === 'sales_admin') {
            $query->where('created_by', auth()->id());
        }

        $orders = $query->limit(50)->get()->map(fn (SalesOrder $o) => [
            'id' => $o->id,
            'date' => $o->created_at->format('d-m-Y'),
            'status' => ucfirst($o->status),
            'items_count' => $o->items->count(),
            'total_amount' => (float) $o->total_amount,
            'subtotal' => (float) $o->subtotal,
            'gst_rate' => (int) ($o->gst_rate ?? 18),
            'gst_amount' => (float) $o->gst_amount,
            'discount_amount' => (float) $o->discount_amount,
            'notes' => $o->notes,
            'items' => $o->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'product_name' => $i->product->name.' ('.$i->product->sku.')',
                'quantity' => $i->quantity,
                'price' => (float) $i->price,
                'total' => (float) $i->total,
            ])->values(),
        ]);

        return response()->json($orders);
    }

    /**
     * Line items for a specific order (copy into create form).
     */
    public function recentItems(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'required|exists:sales_orders,id',
        ]);

        $customer = Customer::where('company_id', $companyId)->findOrFail($request->customer_id);

        if (auth()->user()->role === 'sales_admin') {
            abort_unless($customer->created_by === auth()->id(), 403);
        }

        $order = SalesOrder::where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->where('id', $request->order_id)
            ->with('items.product')
            ->firstOrFail();

        if (auth()->user()->role === 'sales_admin') {
            abort_unless($order->created_by === auth()->id(), 403);
        }

        $items = $order->items->map(fn ($i) => [
            'product_id' => $i->product_id,
            'product_name' => $i->product->name.' ('.$i->product->sku.')',
            'quantity' => $i->quantity,
            'price' => (float) $i->price,
        ]);

        return response()->json([
            'order_id' => $order->id,
            'gst_rate' => (int) $order->gst_rate,
            'discount_amount' => (float) $order->discount_amount,
            'notes' => $order->notes,
            'items' => $items,
        ]);
    }

    public function approve(SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        abort_if(auth()->user()->role === 'sales_admin', 403);

        if ($order->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending orders can be approved.']);
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        ActivityLogService::log('sales.approved', "Sales order #{$order->id} approved.");

        return back()->with('success', 'Order approved successfully.');
    }

    public function reject(SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        abort_if(auth()->user()->role === 'sales_admin', 403);

        if ($order->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending orders can be rejected.']);
        }

        $order->update(['status' => 'rejected']);

        ActivityLogService::log('sales.rejected', "Sales order #{$order->id} rejected.");

        return back()->with('success', 'Order rejected.');
    }

    public function markDispatched(SalesOrder $order)
    {
        abort_if(auth()->user()->isSalesAdmin(), 403);
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (! $order->canMarkDispatched()) {
            return back()->withErrors(['status' => 'Only approved orders can be marked as dispatched.']);
        }

        $order->update(['status' => 'dispatched']);

        ActivityLogService::log('sales.dispatched', "Sales order #{$order->id} marked as dispatched.");

        return back()->with('success', 'Order marked as dispatched.');
    }

    public function markReceivingOk(SalesOrder $order)
    {
        abort_if(auth()->user()->isSalesAdmin(), 403);
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (! $order->canMarkReceivingOk()) {
            return back()->withErrors(['receiving_ok' => 'Invoice must be uploaded before marking receiving ok.']);
        }

        $order->update([
            'receiving_ok' => true,
            'receiving_ok_at' => now(),
        ]);

        ActivityLogService::log('sales.receiving_ok', "Sales order #{$order->id} marked as receiving ok.");

        return back()->with('success', 'Order marked as Receiving Ok.');
    }

    public function destroy(SalesOrder $order)
    {
        abort_if(auth()->user()->isSalesAdmin(), 403);
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        $orderId = $order->id;
        $order->delete();

        ActivityLogService::log('sales.deleted', "Sales order #{$orderId} soft-deleted.");

        return redirect('/sales/orders')->with('success', "Order #{$orderId} deleted successfully.");
    }

    public function uploadInvoice(Request $request, SalesOrder $order)
    {
        abort_if(auth()->user()->isSalesAdmin(), 403);
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (! $order->isDispatched()) {
            return back()->withErrors(['invoice' => 'Invoice can only be uploaded for dispatched orders.']);
        }

        $request->validate([
            'invoice' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'invoice.max' => 'Invoice file must not exceed 5 MB.',
        ]);

        if ($order->invoice_path && Storage::disk('public')->exists($order->invoice_path)) {
            Storage::disk('public')->delete($order->invoice_path);
        }

        $path = $request->file('invoice')->store(
            'invoices/' . $order->company_id,
            'public'
        );

        $order->update(['invoice_path' => $path]);

        ActivityLogService::log('sales.invoice_uploaded', "Invoice uploaded for sales order #{$order->id}.");

        return back()->with('success', 'Invoice uploaded successfully.');
    }

    public function downloadInvoice(SalesOrder $order)
    {
        abort_if(auth()->user()->isSalesAdmin(), 403);
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (! $order->invoice_path || ! Storage::disk('public')->exists($order->invoice_path)) {
            abort(404, 'Invoice not found.');
        }

        $extension = pathinfo($order->invoice_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download(
            $order->invoice_path,
            'invoice-order-' . $order->id . '.' . $extension
        );
    }

    public function export(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $query = SalesOrder::where('company_id', $companyId)
            ->with('customer', 'firm', 'creator', 'items.product', 'payments');

        if (auth()->user()->role === 'sales_admin') {
            $query->where('created_by', auth()->id());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('firm_id')) {
            $query->where('firm_id', $request->firm_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $orders = $query->oldest()->get();

        $filename = 'sales_orders_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Order Ref',
                'Order Date',
                'Firm Name',
                'Customer Name',
                'Product Name',
                'Product SKU',
                'Qty',
                'Rate',
                'GST Rate',
                'Discount',
                'Notes',
                'Status',
                'Paid Amount',
                'Payment Method',
                'Receiving Ok',
                'Driver Name',
                'Driver WhatsApp',
                'Driver Vehicle',
                'Delivery Date',
                'Line Total',
                'Sub Total',
                'GST Amount',
                'Grand Total',
                'Pending',
                'Created By',
            ]);

            foreach ($orders as $order) {
                $subtotal = (float) ($order->subtotal > 0 ? $order->subtotal : $order->items->sum('total'));
                $gstRate = $order->gst_rate === null ? 18 : (int) $order->gst_rate;
                $gstAmount = $gstRate === 0
                    ? 0.0
                    : (float) ($order->gst_amount > 0 ? $order->gst_amount : round($subtotal * $gstRate / 100, 2));
                $discount = (float) ($order->discount_amount ?? 0);
                $paymentMethod = $order->payments
                    ->sortByDesc('payment_date')
                    ->first()
                    ?->payment_method ?? '';

                $productNames = $order->items
                    ->map(fn ($item) => $item->product?->name)
                    ->filter()
                    ->unique()
                    ->implode('; ');
                $productSkus = $order->items
                    ->map(fn ($item) => $item->product?->sku)
                    ->filter()
                    ->unique()
                    ->implode('; ');
                $qty = (int) $order->items->sum('quantity');
                $rates = $order->items->map(fn ($item) => (float) $item->price)->unique()->values();
                $rate = $rates->count() === 1 ? $this->csvMoney((float) $rates->first()) : '';
                $lineTotal = (float) $order->items->sum(function ($item) {
                    return (float) ($item->total ?: round($item->quantity * $item->price, 2));
                });

                fputcsv($handle, [
                    $order->id,
                    $order->created_at?->format('d-m-Y') ?? '',
                    $order->firm?->name ?? '',
                    $order->customer?->name ?? '',
                    $productNames,
                    $productSkus,
                    $qty > 0 ? $qty : '',
                    $rate,
                    $gstRate,
                    $this->csvMoney($discount),
                    $order->notes ?? '',
                    $order->status,
                    $this->csvMoney((float) $order->paid_amount),
                    $paymentMethod,
                    $order->receiving_ok ? 'Yes' : 'No',
                    $order->driver_name ?? '',
                    $this->csvWhatsapp($order->driver_whatsapp),
                    $order->driver_vehicle ?? '',
                    $this->csvDate($order->delivery_date),
                    $lineTotal > 0 ? $this->csvMoney($lineTotal) : '',
                    $this->csvMoney($subtotal),
                    $this->csvMoney($gstAmount),
                    $this->csvMoney((float) $order->total_amount),
                    $this->csvMoney((float) $order->pending_amount),
                    $order->creator?->name ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function csvMoney(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function csvWhatsapp(?string $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '') {
            return '';
        }

        // Leading tab keeps Excel from turning the number into 9.15E+09.
        return "\t" . $digits;
    }

    private function csvDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable) {
            return '';
        }
    }

    public function importTemplate()
    {
        $filename = 'sales_orders_import_template.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Order Ref',
                'Order Date',
                'Firm Name',
                'Customer Name',
                'Product Name',
                'Product SKU',
                'Qty',
                'Rate',
                'GST Rate',
                'Discount',
                'Notes',
                'Status',
                'Paid Amount',
                'Payment Method',
                'Receiving Ok',
                'Driver Name',
                'Driver WhatsApp',
                'Driver Vehicle',
                'Delivery Date',
            ]);
            // Two line items share Order Ref so they become one order.
            fputcsv($handle, [
                'SO-001',
                now()->format('d-m-Y'),
                'Main Firm',
                'SAMPLE TRADERS',
                'Sample Product A',
                'SKU-A',
                '10',
                '100',
                '18',
                '0',
                'Sample remark',
                'approved',
                '0',
                'cash',
                'No',
                '',
                '',
                '',
                '',
            ]);
            fputcsv($handle, [
                'SO-001',
                now()->format('d-m-Y'),
                'Main Firm',
                'SAMPLE TRADERS',
                'Sample Product B',
                'SKU-B',
                '5',
                '50',
                '18',
                '0',
                'Sample remark',
                'approved',
                '0',
                'cash',
                'No',
                '',
                '',
                '',
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

        $user = auth()->user();
        $companyId = $user->company_id;
        $isAdmin = $user->role === 'admin';
        $path = $request->file('csv_file')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return back()->with('error', 'Unable to read the uploaded CSV file.');
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            return back()->with('error', 'The CSV file is empty.');
        }

        $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerRow[0]);
        $map = $this->mapSalesOrderImportHeaders($headerRow);

        if (! isset($map['customer_name']) || ! isset($map['firm_name']) || ! isset($map['quantity']) || ! isset($map['price'])) {
            fclose($handle);
            $found = implode(', ', array_map(fn ($h) => '"' . trim((string) $h) . '"', $headerRow));

            return back()->with(
                'error',
                'CSV must include Firm Name, Customer Name, Qty, and Rate columns. Found headers: '
                . ($found !== '' ? $found : '(none)')
                . '. Download the template for the correct format.'
            );
        }

        if (! isset($map['product_name']) && ! isset($map['product_sku'])) {
            fclose($handle);

            return back()->with('error', 'CSV must include Product Name and/or Product SKU. Download the template for the correct format.');
        }

        $groups = [];
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if ($this->csvRowIsEmpty($row)) {
                continue;
            }

            $customerName = $this->csvCell($row, $map, 'customer_name');
            $firmName = $this->csvCell($row, $map, 'firm_name');
            $productName = $this->csvCell($row, $map, 'product_name');
            $productSku = $this->csvCell($row, $map, 'product_sku');
            $qty = (int) preg_replace('/[^\d]/', '', $this->csvCell($row, $map, 'quantity'));
            $price = $this->parseCsvMoney($this->csvCell($row, $map, 'price'));
            $gstRate = $this->parseCsvGstRate($this->csvCell($row, $map, 'gst_rate'));
            $discount = $this->parseCsvMoney($this->csvCell($row, $map, 'discount'));
            $notes = $this->csvCell($row, $map, 'notes');
            $orderRef = $this->csvCell($row, $map, 'order_ref');
            $orderDate = $this->parseCsvDate($this->csvCell($row, $map, 'order_date'));
            $status = $this->csvCell($row, $map, 'status');
            $paidAmount = $this->parseCsvMoney($this->csvCell($row, $map, 'paid_amount'));
            $paymentMethod = strtolower($this->csvCell($row, $map, 'payment_method'));
            $receivingOk = $this->parseCsvYes($this->csvCell($row, $map, 'receiving_ok'));
            $driverName = $this->csvCell($row, $map, 'driver_name');
            $driverWhatsapp = preg_replace('/\D/', '', $this->csvCell($row, $map, 'driver_whatsapp'));
            $driverVehicle = $this->csvCell($row, $map, 'driver_vehicle');
            $deliveryDate = $this->parseCsvDate($this->csvCell($row, $map, 'delivery_date'));

            if ($customerName === '' || $firmName === '' || ($productName === '' && $productSku === '') || $qty < 1 || $price <= 0) {
                $errors[] = "Row {$rowNum}: firm, customer, product (name or SKU), qty (>0) and rate (>0) are required.";
                continue;
            }

            $groupKey = $orderRef !== ''
                ? 'ref:' . mb_strtolower($orderRef)
                : 'auto:' . mb_strtolower($customerName) . '|' . mb_strtolower($firmName) . '|' . mb_strtolower($notes) . '|' . ($orderDate?->toDateString() ?? '');

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'order_ref' => $orderRef,
                    'customer_name' => $customerName,
                    'firm_name' => $firmName,
                    'gst_rate' => $gstRate,
                    'discount' => $discount,
                    'notes' => $notes,
                    'order_date' => $orderDate,
                    'status' => $status,
                    'paid_amount' => $paidAmount,
                    'payment_method' => $paymentMethod,
                    'receiving_ok' => $receivingOk,
                    'driver_name' => $driverName,
                    'driver_whatsapp' => $driverWhatsapp,
                    'driver_vehicle' => $driverVehicle,
                    'delivery_date' => $deliveryDate,
                    'items' => [],
                    'rows' => [],
                ];
            } else {
                $group = &$groups[$groupKey];
                if ($group['gst_rate'] === 18 && $gstRate !== 18) {
                    $group['gst_rate'] = $gstRate;
                }
                if ($group['discount'] <= 0 && $discount > 0) {
                    $group['discount'] = $discount;
                }
                if ($group['notes'] === '' && $notes !== '') {
                    $group['notes'] = $notes;
                }
                if ($group['status'] === '' && $status !== '') {
                    $group['status'] = $status;
                }
                if ($group['paid_amount'] <= 0 && $paidAmount > 0) {
                    $group['paid_amount'] = $paidAmount;
                }
                if ($group['payment_method'] === '' && $paymentMethod !== '') {
                    $group['payment_method'] = $paymentMethod;
                }
                if (! $group['receiving_ok'] && $receivingOk) {
                    $group['receiving_ok'] = true;
                }
                if ($group['driver_name'] === '' && $driverName !== '') {
                    $group['driver_name'] = $driverName;
                }
                if ($group['driver_whatsapp'] === '' && $driverWhatsapp !== '') {
                    $group['driver_whatsapp'] = $driverWhatsapp;
                }
                if ($group['driver_vehicle'] === '' && $driverVehicle !== '') {
                    $group['driver_vehicle'] = $driverVehicle;
                }
                if ($group['order_date'] === null && $orderDate) {
                    $group['order_date'] = $orderDate;
                }
                if ($group['delivery_date'] === null && $deliveryDate) {
                    $group['delivery_date'] = $deliveryDate;
                }
                unset($group);
            }

            $groups[$groupKey]['items'][] = [
                'product_name' => $productName,
                'product_sku' => $productSku,
                'quantity' => $qty,
                'price' => $price,
            ];
            $groups[$groupKey]['rows'][] = $rowNum;
        }

        fclose($handle);

        $customerQuery = Customer::where('company_id', $companyId);
        if ($user->role === 'sales_admin') {
            $customerQuery->where('created_by', $user->id);
        }
        $customersByName = $customerQuery->get()->keyBy(fn (Customer $c) => mb_strtolower(trim($c->name)));
        $firmsByName = Firm::where('company_id', $companyId)->get()->keyBy(fn (Firm $f) => mb_strtolower(trim($f->name)));
        $products = Product::where('company_id', $companyId)->get();
        $productsByName = $products->keyBy(fn (Product $p) => mb_strtolower(trim($p->name)));
        $productsBySku = $products->filter(fn (Product $p) => $p->sku)->keyBy(fn (Product $p) => mb_strtolower(trim($p->sku)));

        $created = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            $rowLabel = implode(',', $group['rows']);
            $customer = $customersByName->get(mb_strtolower($group['customer_name']));

            if (! $customer) {
                $skipped++;
                $errors[] = "Customer '{$group['customer_name']}' not found (rows {$rowLabel}).";
                continue;
            }

            $firm = $firmsByName->get(mb_strtolower($group['firm_name']));
            if (! $firm) {
                $skipped++;
                $errors[] = "Firm '{$group['firm_name']}' not found (rows {$rowLabel}).";
                continue;
            }

            $itemsData = [];
            $itemError = false;
            foreach ($group['items'] as $item) {
                $product = $this->matchImportedProduct($item['product_name'], $item['product_sku'], $productsByName, $productsBySku);
                if (! $product) {
                    $label = $item['product_sku'] !== '' ? $item['product_sku'] : $item['product_name'];
                    $skipped++;
                    $errors[] = "Product '{$label}' not found (rows {$rowLabel}).";
                    $itemError = true;
                    break;
                }
                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ];
            }

            if ($itemError || empty($itemsData)) {
                continue;
            }

            $totals = $this->calculateOrderTotals(
                $itemsData,
                (int) $group['gst_rate'],
                (float) $group['discount']
            );

            $status = $this->resolveImportedStatus($group['status'], $isAdmin);
            $paidAmount = min(max(0, (float) $group['paid_amount']), $totals['grand_total']);
            $paymentMethod = in_array($group['payment_method'], ['cash', 'bank_transfer', 'upi', 'cheque', 'other'], true)
                ? $group['payment_method']
                : 'cash';

            if (! $isAdmin) {
                $paidAmount = 0;
                $status = 'pending';
            } elseif ($paidAmount > 0 && $status === 'pending') {
                $status = 'approved';
            }

            $receivingOk = $isAdmin && $group['receiving_ok'] && $status === 'dispatched';
            $driverName = $isAdmin && $group['driver_name'] !== '' ? $group['driver_name'] : null;
            $driverWhatsapp = $isAdmin && strlen((string) $group['driver_whatsapp']) >= 10
                ? substr((string) $group['driver_whatsapp'], -15)
                : null;
            $driverVehicle = $isAdmin && $group['driver_vehicle'] !== '' ? $group['driver_vehicle'] : null;
            $deliveryDate = $isAdmin && $group['delivery_date'] ? $group['delivery_date']->toDateString() : null;

            if ($driverName && ! $driverWhatsapp) {
                $errors[] = "Order for '{$group['customer_name']}' (rows {$rowLabel}): driver WhatsApp missing, driver fields skipped.";
                $driverName = null;
                $driverVehicle = null;
                $deliveryDate = null;
            }

            DB::transaction(function () use (
                $companyId, $user, $customer, $firm, $isAdmin, $totals, $itemsData, $group,
                $status, $paidAmount, $paymentMethod, $receivingOk, $driverName, $driverWhatsapp,
                $driverVehicle, $deliveryDate, &$created
            ) {
                $pending = max(0, round($totals['grand_total'] - $paidAmount, 2));

                $order = SalesOrder::create([
                    'company_id' => $companyId,
                    'customer_id' => $customer->id,
                    'firm_id' => $firm->id,
                    'subtotal' => $totals['subtotal'],
                    'gst_rate' => $totals['gst_rate'],
                    'gst_amount' => $totals['gst_amount'],
                    'discount_amount' => $totals['discount_amount'],
                    'total_amount' => $totals['grand_total'],
                    'paid_amount' => $paidAmount,
                    'pending_amount' => $pending,
                    'status' => $status,
                    'approved_by' => $isAdmin && $status !== 'pending' && $status !== 'rejected' ? $user->id : null,
                    'created_by' => $user->id,
                    'notes' => $group['notes'] !== '' ? $group['notes'] : null,
                    'driver_name' => $driverName,
                    'driver_whatsapp' => $driverWhatsapp,
                    'driver_vehicle' => $driverVehicle,
                    'delivery_date' => $deliveryDate,
                    'receiving_ok' => $receivingOk,
                    'receiving_ok_at' => $receivingOk ? now() : null,
                ]);

                if ($group['order_date'] instanceof Carbon) {
                    $order->created_at = $group['order_date']->startOfDay();
                    $order->saveQuietly();
                }

                foreach ($itemsData as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => round($item['quantity'] * $item['price'], 2),
                    ]);
                }

                if ($paidAmount > 0) {
                    Payment::create([
                        'company_id' => $companyId,
                        'sales_order_id' => $order->id,
                        'amount' => $paidAmount,
                        'payment_date' => ($group['order_date'] instanceof Carbon)
                            ? $group['order_date']->toDateString()
                            : now()->toDateString(),
                        'payment_method' => $paymentMethod,
                    ]);
                }

                $created++;
            });
        }

        ActivityLogService::log(
            'sales.imported',
            "Sales orders CSV imported. Created: {$created}, Skipped groups: {$skipped}."
        );

        $message = "Import complete: {$created} order(s) created";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }
        $message .= '.';

        if (! empty($errors)) {
            $message .= ' Issues: ' . implode(' ', array_slice($errors, 0, 8));
            if (count($errors) > 8) {
                $message .= ' …';
            }
        }

        if ($created === 0) {
            return redirect('/sales/orders')->with('error', $message);
        }

        return redirect('/sales/orders')->with('success', $message);
    }

    private function mapSalesOrderImportHeaders(array $headerRow): array
    {
        $aliases = [
            'order_ref' => ['order ref', 'order reference', 'order #', 'order no', 'order no.', 'bill no', 'bill #'],
            'order_date' => ['order date', 'date', 'bill date'],
            'firm_name' => ['firm name', 'firm'],
            'customer_name' => ['customer name', 'customer', 'party name', 'account name'],
            'product_name' => ['product name', 'product'],
            'product_sku' => ['product sku', 'sku', 'item code'],
            'quantity' => ['qty', 'quantity'],
            'price' => ['rate', 'price', 'rate (rs)', 'rate (rs.)'],
            'gst_rate' => ['gst rate', 'gst rate (%)', 'gst'],
            'discount' => ['discount', 'discount (rs)', 'discount (rs.)'],
            'paid_amount' => ['paid amount', 'paid', 'paid (rs)', 'paid (rs.)'],
            'payment_method' => ['payment method', 'pay method'],
            'status' => ['status'],
            'receiving_ok' => ['receiving ok', 'receiving_ok'],
            'notes' => ['notes', 'note', 'remark', 'remarks'],
            'driver_name' => ['driver name', 'driver'],
            'driver_whatsapp' => ['driver whatsapp', 'whatsapp'],
            'driver_vehicle' => ['driver vehicle', 'vehicle'],
            'delivery_date' => ['delivery date'],
        ];

        $map = [];
        foreach ($headerRow as $index => $label) {
            $key = $this->normalizeCsvHeader((string) $label);
            foreach ($aliases as $field => $names) {
                if (in_array($key, $names, true) && ! isset($map[$field])) {
                    $map[$field] = $index;
                }
            }
        }

        return $map;
    }

    private function csvCell(array $row, array $map, string $key): string
    {
        if (! isset($map[$key])) {
            return '';
        }

        return trim((string) ($row[$map[$key]] ?? ''));
    }

    private function normalizeCsvHeader(string $label): string
    {
        $label = preg_replace('/^\xEF\xBB\xBF/', '', $label) ?? $label;
        $label = strtolower(trim($label));
        $label = str_replace(['₹', '_'], ['rs', ' '], $label);

        return preg_replace('/\s+/', ' ', $label) ?? $label;
    }

    private function parseCsvMoney(string $value): float
    {
        $value = str_ireplace(['₹', 'rs.', 'rs', ','], '', $value);

        return (float) preg_replace('/[^\d.\-]/', '', $value);
    }

    private function parseCsvGstRate(string $value): int
    {
        $rate = (int) preg_replace('/[^\d]/', '', $value);
        if ($value === '') {
            return 18;
        }

        return in_array($rate, [0, 5, 18], true) ? $rate : 18;
    }

    private function parseCsvYes(string $value): bool
    {
        $value = strtolower(trim($value));

        return in_array($value, ['1', 'yes', 'y', 'true', 'receiving ok', 'ok'], true);
    }

    private function parseCsvDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return null;
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'd-m-Y H:i', 'd/m/Y H:i', 'Y-m-d H:i:s'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveImportedStatus(string $value, bool $isAdmin): string
    {
        if (! $isAdmin) {
            return 'pending';
        }

        $value = strtolower(trim($value));
        $value = str_replace(' ', '_', $value);

        return match ($value) {
            'pending' => 'pending',
            'rejected' => 'rejected',
            'dispatched' => 'dispatched',
            'paid' => 'approved',
            default => 'approved',
        };
    }

    private function matchImportedProduct(
        string $name,
        string $sku,
        $productsByName,
        $productsBySku
    ): ?Product {
        if ($sku !== '') {
            $found = $productsBySku->get(mb_strtolower($sku));
            if ($found) {
                return $found;
            }
        }

        if ($name !== '') {
            $found = $productsByName->get(mb_strtolower($name));
            if ($found) {
                return $found;
            }

            if (preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/', $name, $matches)) {
                $found = $productsBySku->get(mb_strtolower($matches[2]))
                    ?: $productsByName->get(mb_strtolower(trim($matches[1])));
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
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

    /**
     * @param  array<int, array{quantity: int|float, price: int|float}>  $items
     * @return array{subtotal: float, gst_rate: int, gst_amount: float, discount_amount: float, grand_total: float}
     */
    private function calculateOrderTotals(array $items, int $gstRate, float $discountAmount): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['quantity'] * (float) $item['price'];
        }
        $subtotal = round($subtotal, 2);

        if (! in_array($gstRate, [0, 5, 18], true)) {
            $gstRate = 18;
        }

        $gstAmount = $gstRate === 0 ? 0.0 : round($subtotal * $gstRate / 100, 2);
        $maxDiscount = $subtotal + $gstAmount;
        $discountAmount = round(min(max(0, $discountAmount), $maxDiscount), 2);
        $grandTotal = round($subtotal + $gstAmount - $discountAmount, 2);

        return [
            'subtotal' => $subtotal,
            'gst_rate' => $gstRate,
            'gst_amount' => $gstAmount,
            'discount_amount' => $discountAmount,
            'grand_total' => $grandTotal,
        ];
    }

    private function authorizeOrderAccess(SalesOrder $order): void
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (auth()->user()->isSalesAdmin()) {
            abort_unless($order->created_by === auth()->id(), 403);
        }
    }

    private function activeFirmsForCompany(int $companyId)
    {
        return Firm::where('company_id', $companyId)->active()->orderBy('name')->get();
    }

    private function firmsForOrderForm(int $companyId, ?int $currentFirmId = null)
    {
        return Firm::where('company_id', $companyId)
            ->where(function ($query) use ($currentFirmId) {
                $query->where('status', 'active');
                if ($currentFirmId) {
                    $query->orWhere('id', $currentFirmId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function firmValidationRule(int $companyId, ?int $currentFirmId = null): array
    {
        return [
            'required',
            Rule::exists('firms', 'id')->where(function ($query) use ($companyId, $currentFirmId) {
                $query->where('company_id', $companyId)
                    ->where(function ($statusQuery) use ($currentFirmId) {
                        $statusQuery->where('status', 'active');
                        if ($currentFirmId) {
                            $statusQuery->orWhere('id', $currentFirmId);
                        }
                    });
            }),
        ];
    }
}
