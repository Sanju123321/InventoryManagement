<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\RawMaterial;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionLogController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $logs = ProductionLog::where('company_id', $companyId)->with('product')->latest('production_date')->paginate(15);
        return view('production-logs.index', compact('logs'));
    }

    public function create()
    {
        $products = Product::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        return view('production-logs.create', compact('products'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_produced' => 'required|integer|min:1',
            'production_date' => 'required|date',
        ]);

        $product = Product::where('company_id', $companyId)->findOrFail($request->product_id);
        $bomEntries = BillOfMaterial::where('company_id', $companyId)->where('product_id', $product->id)->get();

        if ($bomEntries->isEmpty()) {
            return back()->withErrors(['product_id' => 'No Bill of Materials found for this product. Please add BOM entries first.'])->withInput();
        }

        // Check sufficient stock and calculate production cost
        $totalCost = 0;
        foreach ($bomEntries as $bom) {
            $totalNeeded = $bom->quantity_required * $request->quantity_produced;
            $material = RawMaterial::where('company_id', $companyId)->find($bom->material_id);

            if ($material->stock_qty < $totalNeeded) {
                return back()->withErrors([
                    'quantity_produced' => "Insufficient stock for {$material->name}. Available: {$material->stock_qty} {$material->unit}, Required: {$totalNeeded} {$material->unit}."
                ])->withInput();
            }

            $totalCost += $totalNeeded * $material->unit_cost;
        }

        DB::transaction(function () use ($request, $bomEntries, $companyId) {
            $productionLog = ProductionLog::create([
                'company_id' => $companyId,
                'product_id' => $request->product_id,
                'quantity_produced' => $request->quantity_produced,
                'production_date' => $request->production_date,
            ]);

            foreach ($bomEntries as $bom) {
                $qtyUsed = $bom->quantity_required * $request->quantity_produced;
                $material = RawMaterial::where('company_id', $companyId)->find($bom->material_id);

                $material->decrement('stock_qty', $qtyUsed);

                InventoryTransaction::create([
                    'company_id' => $companyId,
                    'material_id' => $bom->material_id,
                    'type' => 'out',
                    'quantity' => $qtyUsed,
                    'stock_after' => $material->fresh()->stock_qty,
                ]);

                // Fire low-stock alert if stock has fallen at or below the alert threshold
                $fresh = $material->fresh();
                if ($fresh->stock_qty <= $fresh->min_stock_alert) {
                    try {
                        app(NotificationService::class)->notifyLowStockMaterial($fresh, $companyId);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Low-stock notification failed: ' . $e->getMessage());
                    }
                }
            }
        });

        $costPerUnit = $request->quantity_produced > 0 ? round($totalCost / $request->quantity_produced, 2) : 0;

        ActivityLogService::log('production.logged', "Produced {$request->quantity_produced} unit(s) of '{$product->name}'. Material cost: ₹" . number_format($totalCost, 2) . ".");

        return redirect('/production')->with('success', "Production logged successfully. Total material cost: ₹" . number_format($totalCost, 2) . " (₹{$costPerUnit}/unit). Raw material stock updated.");
    }

    public function show(ProductionLog $productionLog)
    {
        abort_unless($productionLog->company_id === auth()->user()->company_id, 403);
        $productionLog->load('product');

        // Calculate production cost
        $companyId = auth()->user()->company_id;
        $bomEntries = BillOfMaterial::where('company_id', $companyId)
            ->where('product_id', $productionLog->product_id)
            ->with('material')
            ->get();

        $totalCost = 0;
        $materialCosts = [];
        foreach ($bomEntries as $bom) {
            $qtyUsed = $bom->quantity_required * $productionLog->quantity_produced;
            $cost = $qtyUsed * $bom->material->unit_cost;
            $totalCost += $cost;
            $materialCosts[] = [
                'material' => $bom->material->name,
                'qty_used' => $qtyUsed,
                'unit' => $bom->material->unit,
                'unit_cost' => $bom->material->unit_cost,
                'cost' => $cost,
            ];
        }

        $costPerUnit = $productionLog->quantity_produced > 0 ? round($totalCost / $productionLog->quantity_produced, 2) : 0;

        return view('production-logs.show', compact('productionLog', 'materialCosts', 'totalCost', 'costPerUnit'));
    }

    public function edit(ProductionLog $productionLog)
    {
        abort_unless($productionLog->company_id === auth()->user()->company_id, 403);
        $products = Product::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        return view('production-logs.edit', compact('productionLog', 'products'));
    }

    public function update(Request $request, ProductionLog $productionLog)
    {
        abort_unless($productionLog->company_id === auth()->user()->company_id, 403);
        $companyId = auth()->user()->company_id;

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_produced' => 'required|integer|min:1',
            'production_date' => 'required|date',
        ]);

        $oldProductId = $productionLog->product_id;
        $oldQty = $productionLog->quantity_produced;

        // Reverse old material deductions
        $oldBomEntries = BillOfMaterial::where('company_id', $companyId)->where('product_id', $oldProductId)->get();

        try {
            DB::transaction(function () use ($oldBomEntries, $oldQty, $companyId, $request, $productionLog) {
                // Add back old materials
                foreach ($oldBomEntries as $bom) {
                    $qtyToRestore = $bom->quantity_required * $oldQty;
                    $material = RawMaterial::where('company_id', $companyId)->lockForUpdate()->find($bom->material_id);
                    $material->increment('stock_qty', $qtyToRestore);

                    InventoryTransaction::create([
                        'company_id' => $companyId,
                        'material_id' => $bom->material_id,
                        'type' => 'in',
                        'quantity' => $qtyToRestore,
                        'stock_after' => $material->fresh()->stock_qty,
                    ]);
                }

                // Deduct new materials
                $newBomEntries = BillOfMaterial::where('company_id', $companyId)->where('product_id', $request->product_id)->get();
                foreach ($newBomEntries as $bom) {
                    $qtyUsed = $bom->quantity_required * $request->quantity_produced;
                    $material = RawMaterial::where('company_id', $companyId)->lockForUpdate()->find($bom->material_id);

                    if ($material->stock_qty < $qtyUsed) {
                        throw new \Exception("Insufficient stock for {$material->name}. Available: {$material->stock_qty} {$material->unit}, Required: {$qtyUsed} {$material->unit}.");
                    }

                    $material->decrement('stock_qty', $qtyUsed);

                    InventoryTransaction::create([
                        'company_id' => $companyId,
                        'material_id' => $bom->material_id,
                        'type' => 'out',
                        'quantity' => $qtyUsed,
                        'stock_after' => $material->fresh()->stock_qty,
                    ]);
                }

                $productionLog->update([
                    'product_id' => $request->product_id,
                    'quantity_produced' => $request->quantity_produced,
                    'production_date' => $request->production_date,
                ]);
            });
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['stock' => $e->getMessage()]);
        }

        return redirect('/production')->with('success', 'Production log updated successfully. Stock adjusted.');
    }

    public function destroy(ProductionLog $productionLog)
    {
        abort_unless($productionLog->company_id === auth()->user()->company_id, 403);
        $companyId = auth()->user()->company_id;

        $bomEntries = BillOfMaterial::where('company_id', $companyId)->where('product_id', $productionLog->product_id)->get();

        DB::transaction(function () use ($bomEntries, $productionLog, $companyId) {
            // Restore materials to stock
            foreach ($bomEntries as $bom) {
                $qtyToRestore = $bom->quantity_required * $productionLog->quantity_produced;
                $material = RawMaterial::where('company_id', $companyId)->find($bom->material_id);
                $material->increment('stock_qty', $qtyToRestore);

                InventoryTransaction::create([
                    'company_id' => $companyId,
                    'material_id' => $bom->material_id,
                    'type' => 'in',
                    'quantity' => $qtyToRestore,
                    'stock_after' => $material->fresh()->stock_qty,
                ]);
            }

            $productionLog->delete();
        });

        return redirect('/production')->with('success', 'Production log deleted. Materials restored to stock.');
    }
}
