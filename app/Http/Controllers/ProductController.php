<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('company_id', auth()->user()->company_id)->latest()->paginate(15);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where('company_id', $companyId)],
            'unit' => 'required|string',
            'custom_unit' => 'required_if:unit,Other|nullable|string|max:50',
        ]);

        $unitValue = $request->unit === 'Other' ? $request->custom_unit : $request->unit;

        Product::create([
            'company_id' => auth()->user()->company_id,
            'name' => $request->name,
            'sku' => $request->sku,
            'unit' => $unitValue,
            'custom_unit' => $request->unit === 'Other' ? $request->custom_unit : null,
        ]);

        ActivityLogService::log('product.created', "Product '{$request->name}' (SKU: {$request->sku}) created.");
        return redirect('/products')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        abort_unless($product->company_id === auth()->user()->company_id, 403);
        return view('products.edit', compact('product'));
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->company_id === auth()->user()->company_id, 403);

        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where('company_id', $companyId)->ignore($product->id)],
            'unit' => 'required|string',
            'custom_unit' => 'required_if:unit,Other|nullable|string|max:50',
        ]);

        $unitValue = $request->unit === 'Other' ? $request->custom_unit : $request->unit;

        $product->update([
            'name' => $request->name,
            'sku' => $request->sku,
            'unit' => $unitValue,
            'custom_unit' => $request->unit === 'Other' ? $request->custom_unit : null,
        ]);

        ActivityLogService::log('product.updated', "Product '{$product->name}' (SKU: {$product->sku}) updated.");
        return redirect('/products')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        abort_unless($product->company_id === auth()->user()->company_id, 403);
        $name = $product->name;
        $product->delete();
        ActivityLogService::log('product.deleted', "Product '{$name}' deleted.");
        return redirect('/products')->with('success', 'Product deleted successfully.');
    }

    public function export()
    {
        $products = Product::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        $filename = 'products_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Name', 'SKU', 'Unit', 'Created At']);

            foreach ($products as $i => $p) {
                fputcsv($handle, [
                    $i + 1,
                    $p->name,
                    $p->sku,
                    $p->unit,
                    $p->created_at->format('d M Y'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
