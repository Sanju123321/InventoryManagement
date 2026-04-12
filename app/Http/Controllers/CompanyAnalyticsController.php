<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\RawMaterial;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyAnalyticsController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $now       = now();
        $from      = $now->copy()->subDays(29)->startOfDay();

        // ── 1. Daily production – last 30 days ────────────────────────────────
        $productionByDay = ProductionLog::select(
                DB::raw('DATE(production_date) as day'),
                DB::raw('SUM(quantity_produced) as total')
            )
            ->where('company_id', $companyId)
            ->where('production_date', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $productionLabels = [];
        $productionData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $productionLabels[] = $now->copy()->subDays($i)->format('d M');
            $productionData[]   = (int) ($productionByDay[$day] ?? 0);
        }

        // ── 2. Revenue trend – last 6 months ──────────────────────────────────
        $revenueTrend = SalesOrder::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->where('company_id', $companyId)
            ->whereIn('status', ['approved', 'delivered', 'paid'])
            ->where('created_at', '>=', $now->copy()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month');

        $trendLabels = [];
        $trendData   = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = $now->copy()->subMonths($i)->format('Y-m');
            $trendLabels[] = $now->copy()->subMonths($i)->format('M Y');
            $trendData[]   = (float) ($revenueTrend[$key] ?? 0);
        }

        // ── 3. Top 10 products by units produced (all time) ───────────────────
        $topProductsByProduction = ProductionLog::select(
                'product_id',
                DB::raw('SUM(quantity_produced) as total')
            )
            ->where('company_id', $companyId)
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $prodProductIds   = $topProductsByProduction->pluck('product_id');
        $prodProductNames = Product::whereIn('id', $prodProductIds)->pluck('name', 'id');

        $topProdLabels = [];
        $topProdData   = [];
        foreach ($topProductsByProduction as $row) {
            $topProdLabels[] = $prodProductNames[$row->product_id] ?? "Product #{$row->product_id}";
            $topProdData[]   = (int) $row->total;
        }

        // ── 4. Top 10 products by revenue ─────────────────────────────────────
        $topProductsByRevenue = SalesOrderItem::select(
                'product_id',
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(quantity) as qty_sold')
            )
            ->whereHas('salesOrder', fn($q) => $q
                ->where('company_id', $companyId)
                ->whereIn('status', ['approved', 'delivered', 'paid'])
            )
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $revProductIds   = $topProductsByRevenue->pluck('product_id');
        $revProductNames = Product::whereIn('id', $revProductIds)->pluck('name', 'id');

        $topRevLabels = [];
        $topRevData   = [];
        foreach ($topProductsByRevenue as $row) {
            $topRevLabels[] = $revProductNames[$row->product_id] ?? "Product #{$row->product_id}";
            $topRevData[]   = (float) $row->revenue;
        }

        // ── 5. Order status breakdown (doughnut) ──────────────────────────────
        $orderStatusCounts = SalesOrder::select('status', DB::raw('COUNT(*) as cnt'))
            ->where('company_id', $companyId)
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $statusLabels = [];
        $statusData   = [];
        $statusColors = [
            'pending'   => '#f6c23e',
            'approved'  => '#1cc88a',
            'rejected'  => '#e74a3b',
            'delivered' => '#4e73df',
            'paid'      => '#36b9cc',
        ];
        $statusColorList = [];
        foreach ($orderStatusCounts as $status => $cnt) {
            $statusLabels[]    = ucfirst($status);
            $statusData[]      = (int) $cnt;
            $statusColorList[] = $statusColors[$status] ?? '#858796';
        }

        // ── 6. Low-stock materials ─────────────────────────────────────────────
        $lowStockMaterials = RawMaterial::where('company_id', $companyId)
            ->whereColumn('stock_qty', '<=', 'min_stock_alert')
            ->orderBy('stock_qty')
            ->get();

        // ── 7. KPI cards ───────────────────────────────────────────────────────
        $kpi = [
            'total_products'   => Product::where('company_id', $companyId)->count(),
            'total_materials'  => RawMaterial::where('company_id', $companyId)->count(),
            'total_production' => ProductionLog::where('company_id', $companyId)->sum('quantity_produced'),
            'total_revenue'    => SalesOrder::where('company_id', $companyId)
                                    ->whereIn('status', ['approved', 'delivered', 'paid'])->sum('total_amount'),
            'pending_revenue'  => SalesOrder::where('company_id', $companyId)
                                    ->where('status', 'pending')->sum('total_amount'),
            'total_orders'     => SalesOrder::where('company_id', $companyId)->count(),
            'orders_this_month'=> SalesOrder::where('company_id', $companyId)
                                    ->whereMonth('created_at', $now->month)
                                    ->whereYear('created_at', $now->year)->count(),
            'low_stock_count'  => RawMaterial::where('company_id', $companyId)
                                    ->whereColumn('stock_qty', '<=', 'min_stock_alert')->count(),
        ];

        return view('analytics.index', compact(
            'kpi',
            'productionLabels', 'productionData',
            'trendLabels', 'trendData',
            'topProdLabels', 'topProdData',
            'topRevLabels', 'topRevData',
            'statusLabels', 'statusData', 'statusColorList',
            'lowStockMaterials'
        ));
    }
}
