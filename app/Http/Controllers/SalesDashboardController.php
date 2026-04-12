<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $dailyIncome = Payment::where('company_id', $companyId)
            ->whereDate('payment_date', today())
            ->sum('amount');

        $totalSales = SalesOrder::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'delivered', 'paid'])
            ->sum('total_amount');

        $totalPending = SalesOrder::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'delivered'])
            ->sum('pending_amount');

        $totalOrders = SalesOrder::where('company_id', $companyId)->count();
        $pendingOrders = SalesOrder::where('company_id', $companyId)->where('status', 'pending')->count();

        $topProducts = SalesOrderItem::select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_revenue'))
            ->whereHas('salesOrder', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('status', ['approved', 'delivered', 'paid']);
            })
            ->groupBy('product_id')
            ->with('product')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $recentOrders = SalesOrder::where('company_id', $companyId)
            ->with('customer')
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = Payment::where('company_id', $companyId)
            ->with('salesOrder.customer')
            ->latest('payment_date')
            ->take(5)
            ->get();

        // ── Chart: Monthly revenue (last 6 months) ─────────────────────
        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i));

        $monthlyData = Payment::where('company_id', $companyId)
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as total")
            ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $monthlyLabels   = $months->map(fn($m) => $m->format('M Y'))->values()->toArray();
        $monthlyRevenues = $months->map(fn($m) => round((float) ($monthlyData[$m->format('Y-m')] ?? 0), 2))->values()->toArray();

        // ── Chart: Order status breakdown ──────────────────────────────
        $statusData   = SalesOrder::where('company_id', $companyId)->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status');
        $statusOrder  = ['pending', 'approved', 'delivered', 'paid', 'rejected'];
        $statusLabels = collect($statusOrder)->filter(fn($s) => $statusData->has($s))->map(fn($s) => ucfirst($s))->values()->toArray();
        $statusCounts = collect($statusOrder)->filter(fn($s) => $statusData->has($s))->map(fn($s) => (int) $statusData[$s])->values()->toArray();

        return view('sales.dashboard', compact(
            'dailyIncome', 'totalSales', 'totalPending', 'totalOrders',
            'pendingOrders', 'topProducts', 'recentOrders', 'recentPayments',
            'monthlyLabels', 'monthlyRevenues', 'statusLabels', 'statusCounts'
        ));
    }
}
