<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::where('company_id', auth()->user()->company_id)
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15);

        return view('product-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('product-categories.create');
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')->where('company_id', $companyId),
            ],
        ]);

        ProductCategory::create([
            'company_id' => $companyId,
            'name' => $request->name,
        ]);

        ActivityLogService::log('product_category.created', "Category '{$request->name}' created.");

        return redirect('/product-categories')->with('success', 'Category created successfully.');
    }

    public function edit(ProductCategory $product_category)
    {
        abort_unless($product_category->company_id === auth()->user()->company_id, 403);

        return view('product-categories.edit', ['productCategory' => $product_category]);
    }

    public function update(Request $request, ProductCategory $product_category)
    {
        abort_unless($product_category->company_id === auth()->user()->company_id, 403);

        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($product_category->id),
            ],
        ]);

        $product_category->update(['name' => $request->name]);

        ActivityLogService::log('product_category.updated', "Category '{$product_category->name}' updated.");

        return redirect('/product-categories')->with('success', 'Category updated successfully.');
    }

    public function destroy(ProductCategory $product_category)
    {
        abort_unless($product_category->company_id === auth()->user()->company_id, 403);

        if ($product_category->products()->exists()) {
            return back()->withErrors(['name' => 'Cannot delete category that has products assigned. Reassign products first.']);
        }

        $name = $product_category->name;
        $product_category->delete();

        ActivityLogService::log('product_category.deleted', "Category '{$name}' deleted.");

        return redirect('/product-categories')->with('success', 'Category deleted successfully.');
    }
}
