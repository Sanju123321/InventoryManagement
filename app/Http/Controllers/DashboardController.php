<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\ProductionLog;
use App\Models\RawMaterial;
use App\Models\SalesOrderItem;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $totalProducts = Product::where('company_id', $companyId)->count();
        $totalMaterials = RawMaterial::where('company_id', $companyId)->count();
        $productionToday = ProductionLog::where('company_id', $companyId)->whereDate('production_date', today())->sum('quantity_produced');
        $lowStockCount = RawMaterial::where('company_id', $companyId)->whereColumn('stock_qty', '<=', 'min_stock_alert')->count();
        $recentProduction = ProductionLog::where('company_id', $companyId)->with('product')->latest('production_date')->take(5)->get();
        $lowStockMaterials = RawMaterial::where('company_id', $companyId)->whereColumn('stock_qty', '<=', 'min_stock_alert')->get();
        $recentTransactions = InventoryTransaction::where('company_id', $companyId)->with('material')->latest()->take(5)->get();

        $productReport = Product::where('company_id', $companyId)->get()->map(function ($product) use ($companyId) {
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

        return view('dashboard', compact(
            'totalProducts',
            'totalMaterials',
            'productionToday',
            'lowStockCount',
            'recentProduction',
            'lowStockMaterials',
            'recentTransactions',
            'productReport'
        ));
    }
}
