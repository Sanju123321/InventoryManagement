<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $query = SalesOrder::where('company_id', $companyId)->with('customer', 'creator');

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

        return view('sales.orders.index', compact('orders', 'customers'));
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

        $products = Product::where('company_id', $companyId)->orderBy('name')->get();
        $productCosts = ProductCost::where('company_id', $companyId)->pluck('selling_price', 'product_id');

        return view('sales.orders.create', compact('customers', 'products', 'productCosts'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $isAdmin = auth()->user()->role === 'admin';

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
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
        $products = Product::where('company_id', $companyId)->orderBy('name')->get();
        $productCosts = ProductCost::where('company_id', $companyId)->pluck('selling_price', 'product_id');

        $order->load('items.product', 'customer');

        return view('sales.orders.edit', compact('order', 'customers', 'products', 'productCosts'));
    }

    public function update(Request $request, SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        abort_if(auth()->user()->role === 'sales_admin', 403);

        $companyId = auth()->user()->company_id;

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
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
        $order->load('customer', 'items.product', 'payments', 'creator', 'approver', 'company');
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

    public function markDelivered(SalesOrder $order)
    {
        abort_if(auth()->user()->isSalesAdmin(), 403);
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (! $order->canMarkDelivered()) {
            return back()->withErrors(['status' => 'Only approved orders can be marked as delivered.']);
        }

        $order->update(['status' => 'delivered']);

        ActivityLogService::log('sales.delivered', "Sales order #{$order->id} marked as delivered.");

        return back()->with('success', 'Order marked as delivered.');
    }

    public function uploadInvoice(Request $request, SalesOrder $order)
    {
        abort_if(auth()->user()->isSalesAdmin(), 403);
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (! $order->isDelivered()) {
            return back()->withErrors(['invoice' => 'Invoice can only be uploaded for delivered orders.']);
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
        $query = SalesOrder::where('company_id', $companyId)->with('customer', 'creator', 'items.product');

        if (auth()->user()->role === 'sales_admin') {
            $query->where('created_by', auth()->id());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $orders = $query->oldest()->get();

        $filename = 'sales_orders_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order #', 'Date', 'Customer', 'Product', 'Qty', 'Rate (₹)', 'Line Total (₹)', 'Order Total (₹)', 'Paid (₹)', 'Pending (₹)', 'Status', 'Created By']);

            foreach ($orders as $order) {
                $items = $order->items;
                if ($items->isEmpty()) {
                    fputcsv($handle, [
                        $order->id,
                        $order->created_at->format('d-m-Y'),
                        $order->customer?->name ?? '-',
                        '-', '', '', '',
                        number_format($order->total_amount, 2),
                        number_format($order->paid_amount, 2),
                        number_format($order->pending_amount, 2),
                        ucfirst($order->status),
                        $order->creator?->name ?? '-',
                    ]);
                } else {
                    foreach ($items as $i => $item) {
                        fputcsv($handle, [
                            $order->id,
                            $order->created_at->format('d-m-Y'),
                            $order->customer?->name ?? '-',
                            $item->product?->name ?? '-',
                            $item->quantity,
                            number_format($item->price, 2),
                            number_format($item->total, 2),
                            $i === 0 ? number_format($order->total_amount, 2) : '',
                            $i === 0 ? number_format($order->paid_amount, 2) : '',
                            $i === 0 ? number_format($order->pending_amount, 2) : '',
                            $i === 0 ? ucfirst($order->status) : '',
                            $i === 0 ? ($order->creator?->name ?? '-') : '',
                        ]);
                    }
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
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
}
