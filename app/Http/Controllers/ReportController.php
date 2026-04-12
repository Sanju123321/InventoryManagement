<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\ProductionLog;
use App\Models\RawMaterial;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function show(Request $request, string $report = 'daily-production')
    {
        return match ($report) {
            'daily-production' => $this->dailyProduction($request),
            'material-usage' => $this->materialUsage($request),
            'low-stock' => $this->lowStock(),
            'production-history' => $this->productionHistory($request),
            default => redirect('/reports'),
        };
    }

    private function dailyProduction(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $date = $request->get('date', today()->toDateString());
        $logs = ProductionLog::where('company_id', $companyId)
            ->with('product')
            ->whereDate('production_date', $date)
            ->latest()
            ->get();

        return view('reports.daily-production', compact('logs', 'date'));
    }

    private function materialUsage(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());

        $usage = InventoryTransaction::where('company_id', $companyId)
            ->with('material')
            ->where('type', 'out')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy('material_id')
            ->map(function ($group) {
                return [
                    'material' => $group->first()->material,
                    'total_used' => $group->sum('quantity'),
                    'transactions' => $group->count(),
                ];
            });

        return view('reports.material-usage', compact('usage', 'from', 'to'));
    }

    private function lowStock()
    {
        $companyId = auth()->user()->company_id;
        $materials = RawMaterial::where('company_id', $companyId)
            ->whereColumn('stock_qty', '<=', 'min_stock_alert')
            ->orderBy('stock_qty')
            ->get();

        return view('reports.low-stock', compact('materials'));
    }

    private function productionHistory(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $from = $request->get('from', today()->subDays(30)->toDateString());
        $to = $request->get('to', today()->toDateString());

        $logs = ProductionLog::where('company_id', $companyId)
            ->with('product')
            ->whereBetween('production_date', [$from, $to])
            ->latest('production_date')
            ->paginate(20)
            ->appends($request->query());

        return view('reports.production-history', compact('logs', 'from', 'to'));
    }
}
