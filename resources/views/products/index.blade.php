@extends('layouts.app')

@section('title', 'Products')

@section('content')
    <h1 class="mt-4">Products</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Products</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ url('/products') }}" class="row g-3 align-items-start">
                <div class="col-12 col-md-4">
                    <label for="search" class="form-label small text-muted mb-1">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                        value="{{ request('search') }}" placeholder="Name or SKU...">
                </div>
                <div class="col-12 col-md-3">
                    <label for="category_id" class="form-label small text-muted mb-1">Category</label>
                    <select class="form-control" id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                    <a href="{{ url('/products') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><i class="fas fa-box me-1"></i> Products List</div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ url('/product-categories') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-tags me-1"></i> Categories
                </a>
                <a href="{{ route('products.export') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <a href="{{ url('/products/create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i><span class="btn-label">Add Product</span>
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Name</th>
                            <th class="col-hide-mobile">SKU</th>
                            <th class="col-hide-mobile">Category</th>
                            <th class="col-hide-mobile">Unit</th>
                            <th style="min-width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                                <td class="fw-semibold">{{ $product->name }}</td>
                                <td class="col-hide-mobile"><code class="text-muted">{{ $product->sku }}</code></td>
                                <td class="col-hide-mobile">
                                    @if ($product->category)
                                        <span class="badge bg-info text-dark">{{ $product->category->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="col-hide-mobile"><span class="badge bg-secondary rounded-pill">{{ $product->unit }}</span></td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ url('/products/' . $product->id . '/edit') }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit product">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ url('/products/' . $product->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Delete product">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-box fa-2x mb-2 d-block opacity-25"></i>No products found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $products->links() }}</div>
        </div>
    </div>
@endsection
