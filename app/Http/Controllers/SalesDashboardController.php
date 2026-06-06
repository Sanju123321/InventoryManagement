<?php

namespace App\Http\Controllers;

use App\Models\Firm;
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
            ->whereIn('status', ['approved', 'dispatched', 'paid'])
            ->sum('total_amount');

        $totalPending = SalesOrder::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'dispatched'])
            ->sum('pending_amount');

        $totalOrders = SalesOrder::where('company_id', $companyId)->count();
        $pendingOrders = SalesOrder::where('company_id', $companyId)->where('status', 'pending')->count();

        $topProducts = SalesOrderItem::select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_revenue'))
            ->whereHas('salesOrder', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('status', ['approved', 'dispatched', 'paid']);
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
        $statusOrder  = ['pending', 'approved', 'dispatched', 'paid', 'rejected'];
        $statusLabels = collect($statusOrder)->filter(fn($s) => $statusData->has($s))->map(fn($s) => ucfirst($s))->values()->toArray();
        $statusCounts = collect($statusOrder)->filter(fn($s) => $statusData->has($s))->map(fn($s) => (int) $statusData[$s])->values()->toArray();

        $firmWiseSales = $this->buildFirmWiseSales($companyId);
        $firmChartLabels = $firmWiseSales->pluck('firm_name')->values()->toArray();
        $firmChartSales = $firmWiseSales->pluck('total_sales')->map(fn ($v) => round((float) $v, 2))->values()->toArray();

        return view('sales.dashboard', compact(
            'dailyIncome', 'totalSales', 'totalPending', 'totalOrders',
            'pendingOrders', 'topProducts', 'recentOrders', 'recentPayments',
            'monthlyLabels', 'monthlyRevenues', 'statusLabels', 'statusCounts',
            'firmWiseSales', 'firmChartLabels', 'firmChartSales'
        ));
    }

    private function buildFirmWiseSales(int $companyId)
    {
        $activeStatuses = ['approved', 'dispatched', 'paid'];

        $aggregates = SalesOrder::where('company_id', $companyId)
            ->whereIn('status', $activeStatuses)
            ->selectRaw('firm_id, COUNT(*) as order_count, SUM(total_amount) as total_sales, SUM(paid_amount) as total_paid, SUM(pending_amount) as total_pending')
            ->groupBy('firm_id')
            ->get()
            ->keyBy(fn ($row) => $row->firm_id ?? 0);

        $firms = Firm::where('company_id', $companyId)->orderBy('name')->get();

        $rows = $firms->map(function (Firm $firm) use ($aggregates) {
            $stats = $aggregates->get($firm->id);

            return [
                'firm_id' => $firm->id,
                'firm_name' => $firm->name,
                'order_count' => (int) ($stats->order_count ?? 0),
                'total_sales' => (float) ($stats->total_sales ?? 0),
                'total_paid' => (float) ($stats->total_paid ?? 0),
                'total_pending' => (float) ($stats->total_pending ?? 0),
            ];
        });

        $unassigned = $aggregates->get(0);
        if ($unassigned) {
            $rows->push([
                'firm_id' => null,
                'firm_name' => 'Unassigned',
                'order_count' => (int) $unassigned->order_count,
                'total_sales' => (float) $unassigned->total_sales,
                'total_paid' => (float) $unassigned->total_paid,
                'total_pending' => (float) $unassigned->total_pending,
            ]);
        }

        return $rows->sortByDesc('total_sales')->values();
    }
}
