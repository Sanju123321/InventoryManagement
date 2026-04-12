<?php

namespace App\Http\Controllers;

use App\Models\ProductCost;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\SalesOrderItem;
use App\Models\SalesOrder;
use App\Models\BillOfMaterial;
use Illuminate\Http\Request;

class ProductCostController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $costs = ProductCost::where('company_id', $companyId)->with('product')->latest()->paginate(15);
        return view('sales.product-costs.index', compact('costs'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;
        $products = Product::where('company_id', $companyId)
            ->whereDoesntHave('productCost', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderBy('name')->get();

        return view('sales.product-costs.create', compact('products'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'production_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $profit = $request->selling_price - $request->production_cost;

        ProductCost::create([
            'company_id' => $companyId,
            'product_id' => $request->product_id,
            'production_cost' => $request->production_cost,
            'selling_price' => $request->selling_price,
            'profit' => $profit,
        ]);

        return redirect('/sales/product-costs')->with('success', 'Product pricing saved. Profit per unit: ₹' . number_format($profit, 2));
    }

    public function edit(ProductCost $productCost)
    {
        abort_unless($productCost->company_id === auth()->user()->company_id, 403);
        $productCost->load('product');
        return view('sales.product-costs.edit', compact('productCost'));
    }

    public function update(Request $request, ProductCost $productCost)
    {
        abort_unless($productCost->company_id === auth()->user()->company_id, 403);

        $request->validate([
            'production_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $profit = $request->selling_price - $request->production_cost;

        $productCost->update([
            'production_cost' => $request->production_cost,
            'selling_price' => $request->selling_price,
            'profit' => $profit,
        ]);

        return redirect('/sales/product-costs')->with('success', 'Product pricing updated.');
    }

    public function report()
    {
        $companyId = auth()->user()->company_id;

        $products = Product::where('company_id', $companyId)->get()->map(function ($product) use ($companyId) {
            $cost = ProductCost::where('company_id', $companyId)->where('product_id', $product->id)->first();
            $totalProduced = ProductionLog::where('company_id', $companyId)->where('product_id', $product->id)->sum('quantity_produced');

            $totalSold = SalesOrderItem::whereHas('salesOrder', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('status', ['approved', 'delivered', 'paid']);
            })->where('product_id', $product->id)->sum('quantity');

            $totalRevenue = SalesOrderItem::whereHas('salesOrder', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('status', ['approved', 'delivered', 'paid']);
            })->where('product_id', $product->id)->sum('total');

            $availableStock = $totalProduced - $totalSold;
            $totalProfit = $cost ? $totalSold * $cost->profit : 0;

            return [
                'product' => $product,
                'cost' => $cost,
                'total_produced' => $totalProduced,
                'total_sold' => $totalSold,
                'available_stock' => $availableStock,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalProfit,
            ];
        });

        return view('sales.reports.product-report', compact('products'));
    }

    public function export()
    {
        $companyId = auth()->user()->company_id;
        $costs = ProductCost::where('company_id', $companyId)->with('product')->get();

        $filename = 'product_pricing_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($costs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Product', 'SKU', 'Production Cost (₹)', 'Selling Price (₹)', 'Profit / Unit (₹)', 'Created At']);

            foreach ($costs as $i => $c) {
                fputcsv($handle, [
                    $i + 1,
                    $c->product->name ?? '-',
                    $c->product->sku ?? '-',
                    number_format($c->production_cost, 2),
                    number_format($c->selling_price, 2),
                    number_format($c->profit, 2),
                    $c->created_at->format('d M Y'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
