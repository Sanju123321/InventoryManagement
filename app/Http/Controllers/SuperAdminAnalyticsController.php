<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ProductionLog;
use App\Models\RawMaterial;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class SuperAdminAnalyticsController extends Controller
{
    public function index()
    {
        $now  = now();
        $from = $now->copy()->subDays(29)->startOfDay(); // last 30 days

        // ── 1. Total production per day (last 30 days, all companies) ──────────
        $productionByDay = ProductionLog::select(
                DB::raw('DATE(production_date) as day'),
                DB::raw('SUM(quantity_produced) as total')
            )
            ->where('production_date', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        // Fill missing days with 0
        $productionLabels = [];
        $productionData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $productionLabels[] = $now->copy()->subDays($i)->format('d M');
            $productionData[]   = (int) ($productionByDay[$day] ?? 0);
        }

        // ── 2. Total production per company (all time) ─────────────────────────
        $productionByCompany = ProductionLog::select(
                'company_id',
                DB::raw('SUM(quantity_produced) as total')
            )
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        // ── 3. Top 10 companies by revenue (total_amount of approved/delivered/paid orders) ──
        $topByRevenue = SalesOrder::select(
                'company_id',
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as order_count')
            )
            ->whereIn('status', ['approved', 'delivered', 'paid'])
            ->groupBy('company_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $revenueCompanyIds = $topByRevenue->pluck('company_id');
        $revenueCompanies  = Company::whereIn('id', $revenueCompanyIds)->pluck('company_name', 'id');

        $revenueLabels = [];
        $revenueData   = [];
        foreach ($topByRevenue as $row) {
            $revenueLabels[] = $revenueCompanies[$row->company_id] ?? "Company #{$row->company_id}";
            $revenueData[]   = (float) $row->revenue;
        }

        // ── 4. Revenue trend last 6 months (all companies) ────────────────────
        $revenueTrend = SalesOrder::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(total_amount) as revenue')
            )
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

        // ── 5. Low-stock materials (stock_qty <= min_stock_alert) ─────────────
        $lowStockMaterials = RawMaterial::select(
                'raw_materials.id',
                'raw_materials.name',
                'raw_materials.stock_qty',
                'raw_materials.min_stock_alert',
                'raw_materials.unit',
                'raw_materials.company_id',
                'companies.company_name'
            )
            ->join('companies', 'companies.id', '=', 'raw_materials.company_id')
            ->whereColumn('raw_materials.stock_qty', '<=', 'raw_materials.min_stock_alert')
            ->orderBy('raw_materials.stock_qty')
            ->limit(50)
            ->get();

        // ── 6. Low-stock count per company (for chart) ────────────────────────
        $lowStockByCompany = RawMaterial::select(
                'company_id',
                DB::raw('COUNT(*) as cnt')
            )
            ->whereColumn('stock_qty', '<=', 'min_stock_alert')
            ->groupBy('company_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        $lowStockCompanyIds    = $lowStockByCompany->pluck('company_id');
        $lowStockCompanyNames  = Company::whereIn('id', $lowStockCompanyIds)->pluck('company_name', 'id');

        $lowStockLabels = [];
        $lowStockData   = [];
        foreach ($lowStockByCompany as $row) {
            $lowStockLabels[] = $lowStockCompanyNames[$row->company_id] ?? "Company #{$row->company_id}";
            $lowStockData[]   = (int) $row->cnt;
        }

        // ── 7. KPI summary cards ──────────────────────────────────────────────
        $kpi = [
            'total_companies'  => Company::count(),
            'active_companies' => Company::where('status', 'active')->count(),
            'total_revenue'    => SalesOrder::whereIn('status', ['approved', 'delivered', 'paid'])->sum('total_amount'),
            'pending_revenue'  => SalesOrder::where('status', 'pending')->sum('total_amount'),
            'total_production' => ProductionLog::sum('quantity_produced'),
            'total_low_stock'  => RawMaterial::whereColumn('stock_qty', '<=', 'min_stock_alert')->count(),
            'total_orders'     => SalesOrder::count(),
            'orders_this_month'=> SalesOrder::whereMonth('created_at', $now->month)
                                    ->whereYear('created_at', $now->year)->count(),
        ];

        // ── 8. Production by company (all time, top 10) ───────────────────────
        $topProductionByCompany = ProductionLog::select(
                'company_id',
                DB::raw('SUM(quantity_produced) as total')
            )
            ->groupBy('company_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $prodCompanyIds   = $topProductionByCompany->pluck('company_id');
        $prodCompanyNames = Company::whereIn('id', $prodCompanyIds)->pluck('company_name', 'id');

        $prodCompanyLabels = [];
        $prodCompanyData   = [];
        foreach ($topProductionByCompany as $row) {
            $prodCompanyLabels[] = $prodCompanyNames[$row->company_id] ?? "Company #{$row->company_id}";
            $prodCompanyData[]   = (int) $row->total;
        }

        return view('superadmin.analytics', compact(
            'kpi',
            'productionLabels', 'productionData',
            'revenueLabels', 'revenueData',
            'trendLabels', 'trendData',
            'lowStockMaterials',
            'lowStockLabels', 'lowStockData',
            'prodCompanyLabels', 'prodCompanyData'
        ));
    }
}
