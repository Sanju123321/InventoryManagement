<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\RawMaterial;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $materials = RawMaterial::where('company_id', $companyId)->orderBy('name')->get();
        $transactions = InventoryTransaction::where('company_id', $companyId)
            ->with('material')
            ->latest()
            ->paginate(20);

        return view('inventory.index', compact('materials', 'transactions'));
    }

    public function create()
    {
        $materials = RawMaterial::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        return view('inventory.create', compact('materials'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'material_id' => 'required|exists:raw_materials,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.0001',
        ]);

        $material = RawMaterial::where('company_id', $companyId)->findOrFail($request->material_id);

        if ($request->type === 'in') {
            $material->increment('stock_qty', $request->quantity);
        } else {
            if ($material->stock_qty < $request->quantity) {
                return back()->withErrors(['quantity' => 'Insufficient stock. Available: ' . $material->stock_qty])->withInput();
            }
            $material->decrement('stock_qty', $request->quantity);
        }

        InventoryTransaction::create([
            'company_id' => $companyId,
            'material_id' => $request->material_id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'stock_after' => $material->fresh()->stock_qty,
        ]);

        // Fire low-stock notification when material falls at or below its alert level
        $fresh = $material->fresh();
        if ($request->type === 'out' && $fresh->stock_qty <= $fresh->min_stock_alert) {
            try {
                app(NotificationService::class)->notifyLowStockMaterial($fresh, $companyId);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Low-stock notification failed: ' . $e->getMessage());
            }
        }

        return redirect('/inventory')->with('success', 'Inventory transaction recorded.');
    }

    public function show(InventoryTransaction $inventory)
    {
        abort_unless($inventory->company_id === auth()->user()->company_id, 403);
        $inventory->load('material');
        return view('inventory.show', compact('inventory'));
    }

    public function export()
    {
        $companyId = auth()->user()->company_id;
        $transactions = InventoryTransaction::where('company_id', $companyId)
            ->with('material')
            ->latest()
            ->get();

        $filename = 'inventory_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Material', 'Unit', 'Type', 'Quantity', 'Stock After', 'Date']);

            foreach ($transactions as $i => $t) {
                fputcsv($handle, [
                    $i + 1,
                    $t->material->name ?? '-',
                    $t->material->unit ?? '-',
                    strtoupper($t->type),
                    $t->quantity,
                    $t->stock_after,
                    $t->created_at->format('d M Y H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
