<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Product::where('company_id', $companyId)->with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->latest()->paginate(15)->appends($request->query());
        $categories = ProductCategory::where('company_id', $companyId)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = ProductCategory::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where('company_id', $companyId)],
            'unit' => 'required|string',
            'custom_unit' => 'required_if:unit,Other|nullable|string|max:50',
            'category_id' => [
                'required',
                Rule::exists('product_categories', 'id')->where('company_id', $companyId),
            ],
        ]);

        $unitValue = $request->unit === 'Other' ? $request->custom_unit : $request->unit;

        Product::create([
            'company_id' => $companyId,
            'category_id' => $request->category_id,
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

        $categories = ProductCategory::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product', 'categories'));
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
            'category_id' => [
                'required',
                Rule::exists('product_categories', 'id')->where('company_id', $companyId),
            ],
        ]);

        $unitValue = $request->unit === 'Other' ? $request->custom_unit : $request->unit;

        $product->update([
            'name' => $request->name,
            'sku' => $request->sku,
            'unit' => $unitValue,
            'custom_unit' => $request->unit === 'Other' ? $request->custom_unit : null,
            'category_id' => $request->category_id,
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
        $products = Product::where('company_id', auth()->user()->company_id)
            ->with('category')
            ->orderBy('name')
            ->get();

        $filename = 'products_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Name', 'SKU', 'Category', 'Unit', 'Created At']);

            foreach ($products as $i => $p) {
                fputcsv($handle, [
                    $i + 1,
                    $p->name,
                    $p->sku,
                    $p->category?->name ?? '-',
                    $p->unit,
                    $p->created_at->format('d M Y'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
