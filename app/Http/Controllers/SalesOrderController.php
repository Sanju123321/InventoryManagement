<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        // Validate that the customer belongs to the company
        $customer = Customer::findOrFail($request->customer_id);
        abort_unless($customer->company_id === $companyId, 403);

        // Sales admins can only create orders for their own customers
        if (auth()->user()->role === 'sales_admin') {
            abort_unless($customer->created_by === auth()->id(), 403);
        }

        $order = null;

        DB::transaction(function () use ($request, $companyId, &$order) {
            $totalAmount = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['price'];
                $totalAmount += $lineTotal;
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
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'pending_amount' => $totalAmount,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }
        });

        // Check product stock levels after order creation and notify if any product is low
        try {
            $lowItems = [];
            foreach ($order->items as $item) {
                $produced = \App\Models\ProductionLog::where('company_id', $companyId)
                    ->where('product_id', $item->product_id)
                    ->sum('quantity_produced');

                $sold = SalesOrderItem::whereHas('salesOrder', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->whereIn('status', ['approved', 'delivered', 'paid']);
                })->where('product_id', $item->product_id)->sum('quantity');

                $availableStock = $produced - $sold;
                // Warn if remaining stock (after this pending order) would be <= 5
                if (($availableStock - $item->quantity) <= 5) {
                    $lowItems[] = [
                        'name'      => $item->product->name,
                        'available' => $availableStock,
                        'ordered'   => $item->quantity,
                    ];
                }
            }

            if (! empty($lowItems)) {
                app(NotificationService::class)->notifyLowStockProduct($companyId, $order->id, $lowItems);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Order stock notification failed: ' . $e->getMessage());
        }

        ActivityLogService::log('sales.created', "Sales order #{$order->id} created for customer ID {$order->customer_id}. Total: ₹" . number_format($order->total_amount, 2) . ".");

        // Notify admins of the new pending order
        try {
            $order->load('creator', 'customer', 'items.product');
            app(NotificationService::class)->notifyNewOrder($order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('New order admin notification failed: ' . $e->getMessage());
        }

        return redirect('/sales/orders')->with('success', 'Sales order created successfully.');
    }

    public function show(SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        $order->load('customer', 'items.product', 'payments', 'creator', 'approver');
        $companyId = auth()->user()->company_id;
        $productCosts = ProductCost::where('company_id', $companyId)
            ->pluck('selling_price', 'product_id');
        return view('sales.orders.show', compact('order', 'productCosts'));
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

    public function recentItems(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $customerId = $request->customer_id;
        $customer = Customer::where('company_id', $companyId)->findOrFail($customerId);
        $lastOrder = SalesOrder::where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->latest()
            ->with('items.product')
            ->first();
        if (! $lastOrder) return response()->json([]);
        $items = $lastOrder->items->map(fn($i) => [
            'product_id'   => $i->product_id,
            'product_name' => $i->product->name . ' (' . $i->product->sku . ')',
            'quantity'     => $i->quantity,
            'price'        => $i->price,
        ]);
        return response()->json($items);
    }

    public function approve(SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);
        abort_if(auth()->user()->role === 'sales_admin', 403);

        if ($order->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending orders can be approved.']);
        }

        // Check product stock (production logs determine available stock)
        $companyId = auth()->user()->company_id;
        foreach ($order->items as $item) {
            $produced = \App\Models\ProductionLog::where('company_id', $companyId)
                ->where('product_id', $item->product_id)
                ->sum('quantity_produced');

            $sold = SalesOrderItem::whereHas('salesOrder', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('status', ['approved', 'delivered', 'paid']);
            })->where('product_id', $item->product_id)->sum('quantity');

            $availableStock = $produced - $sold;

            if ($availableStock < $item->quantity) {
                $productName = $item->product->name;

                // Notify about insufficient stock
                try {
                    app(NotificationService::class)->notifyOrderInsufficientStock(
                        $companyId, $order->id, $productName, $availableStock, $item->quantity
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Insufficient stock notification failed: ' . $e->getMessage());
                }

                return back()->withErrors([
                    'stock' => "Insufficient stock for {$productName}. Available: {$availableStock}, Required: {$item->quantity}."
                ]);
            }
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        ActivityLogService::log('sales.approved', "Sales order #{$order->id} approved.");

        return back()->with('success', 'Order approved. Stock reserved.');
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
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if ($order->status !== 'approved') {
            return back()->withErrors(['status' => 'Only approved orders can be marked as delivered.']);
        }

        $order->update(['status' => 'delivered']);

        ActivityLogService::log('sales.delivered', "Sales order #{$order->id} marked as delivered.");

        return back()->with('success', 'Order marked as delivered.');
    }

    public function export(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $query = SalesOrder::where('company_id', $companyId)->with('customer', 'creator');

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

        $orders = $query->latest()->get();

        $filename = 'sales_orders_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order #', 'Customer', 'Total (₹)', 'Paid (₹)', 'Pending (₹)', 'Status', 'Created By', 'Date']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->id,
                    $order->customer?->name ?? '-',
                    number_format($order->total_amount, 2),
                    number_format($order->paid_amount, 2),
                    number_format($order->pending_amount, 2),
                    ucfirst($order->status),
                    $order->creator?->name ?? '-',
                    $order->created_at->format('d M Y'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
