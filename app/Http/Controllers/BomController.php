<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\Request;

class BomController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $boms = BillOfMaterial::where('company_id', $companyId)->with('product', 'material')->latest()->paginate(20);
        return view('bom.index', compact('boms'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;
        $products = Product::where('company_id', $companyId)->orderBy('name')->get();
        $materials = RawMaterial::where('company_id', $companyId)->orderBy('name')->get();
        return view('bom.create', compact('products', 'materials'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'materials' => 'required|array|min:1',
            'materials.*.material_id' => 'required|exists:raw_materials,id',
            'materials.*.quantity_required' => 'required|numeric|min:0.001',
        ]);

        foreach ($request->materials as $item) {
            BillOfMaterial::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'product_id' => $request->product_id,
                    'material_id' => $item['material_id'],
                ],
                [
                    'quantity_required' => $item['quantity_required'],
                ]
            );
        }

        return redirect('/bom')->with('success', 'BOM entries created successfully.');
    }

    public function edit(BillOfMaterial $bom)
    {
        abort_unless($bom->company_id === auth()->user()->company_id, 403);
        $companyId = auth()->user()->company_id;
        $products = Product::where('company_id', $companyId)->orderBy('name')->get();
        $materials = RawMaterial::where('company_id', $companyId)->orderBy('name')->get();

        $existingBoms = BillOfMaterial::where('company_id', $companyId)
            ->where('product_id', $bom->product_id)
            ->pluck('quantity_required', 'material_id')
            ->toArray();

        return view('bom.edit', compact('bom', 'products', 'materials', 'existingBoms'));
    }

    public function update(Request $request, BillOfMaterial $bom)
    {
        abort_unless($bom->company_id === auth()->user()->company_id, 403);
        $companyId = auth()->user()->company_id;

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'materials' => 'required|array|min:1',
            'materials.*.material_id' => 'required|exists:raw_materials,id',
            'materials.*.quantity_required' => 'required|numeric|min:0.001',
        ]);

        BillOfMaterial::where('company_id', $companyId)
            ->where('product_id', $bom->product_id)
            ->delete();

        foreach ($request->materials as $item) {
            BillOfMaterial::create([
                'company_id' => $companyId,
                'product_id' => $request->product_id,
                'material_id' => $item['material_id'],
                'quantity_required' => $item['quantity_required'],
            ]);
        }

        return redirect('/bom')->with('success', 'BOM entries updated successfully.');
    }

    public function destroy(BillOfMaterial $bom)
    {
        abort_unless($bom->company_id === auth()->user()->company_id, 403);
        $bom->delete();
        return redirect('/bom')->with('success', 'BOM entry deleted successfully.');
    }
}
