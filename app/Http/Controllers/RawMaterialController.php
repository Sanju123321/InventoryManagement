<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    public function index()
    {
        $materials = RawMaterial::where('company_id', auth()->user()->company_id)->latest()->paginate(15);
        return view('raw-materials.index', compact('materials'));
    }

    public function create()
    {
        return view('raw-materials.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string',
            'custom_unit' => 'required_if:unit,Other|nullable|string|max:50',
            'stock_qty' => 'required|numeric|min:0',
            'min_stock_alert' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $unitValue = $request->unit === 'Other' ? $request->custom_unit : $request->unit;

        RawMaterial::create([
            'company_id' => auth()->user()->company_id,
            'name' => $request->name,
            'unit' => $unitValue,
            'custom_unit' => $request->unit === 'Other' ? $request->custom_unit : null,
            'stock_qty' => $request->stock_qty,
            'min_stock_alert' => $request->min_stock_alert ?? 10,
            'unit_cost' => $request->unit_cost ?? 0,
        ]);

        ActivityLogService::log('material.created', "Raw material '{$request->name}' added (stock: {$request->stock_qty}).");
        return redirect('/materials')->with('success', 'Raw material added successfully.');
    }

    public function edit(RawMaterial $rawMaterial)
    {
        abort_unless($rawMaterial->company_id === auth()->user()->company_id, 403);
        return view('raw-materials.edit', compact('rawMaterial'));
    }

    public function update(Request $request, RawMaterial $rawMaterial)
    {
        abort_unless($rawMaterial->company_id === auth()->user()->company_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string',
            'custom_unit' => 'required_if:unit,Other|nullable|string|max:50',
            'stock_qty' => 'required|numeric|min:0',
            'min_stock_alert' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $unitValue = $request->unit === 'Other' ? $request->custom_unit : $request->unit;

        $rawMaterial->update([
            'name' => $request->name,
            'unit' => $unitValue,
            'custom_unit' => $request->unit === 'Other' ? $request->custom_unit : null,
            'stock_qty' => $request->stock_qty,
            'min_stock_alert' => $request->min_stock_alert,
            'unit_cost' => $request->unit_cost,
        ]);

        // Notify if updated stock is at or below the alert threshold
        $fresh = $rawMaterial->fresh();
        if ($fresh->stock_qty <= $fresh->min_stock_alert) {
            try {
                app(NotificationService::class)->notifyLowStockMaterial($fresh, auth()->user()->company_id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Low-stock notification failed: ' . $e->getMessage());
            }
        }

        ActivityLogService::log('material.updated', "Raw material '{$rawMaterial->name}' updated.");
        return redirect('/materials')->with('success', 'Raw material updated successfully.');
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        abort_unless($rawMaterial->company_id === auth()->user()->company_id, 403);
        $name = $rawMaterial->name;
        $rawMaterial->delete();
        ActivityLogService::log('material.deleted', "Raw material '{$name}' deleted.");
        return redirect('/materials')->with('success', 'Raw material deleted successfully.');
    }
}
