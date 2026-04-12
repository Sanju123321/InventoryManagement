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

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-box me-1"></i> Products List</div>
            <div class="d-flex gap-2">
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
                            <th>SKU</th>
                            <th>Unit</th>
                            <th style="min-width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                                <td class="fw-semibold">{{ $product->name }}</td>
                                <td><code class="text-muted">{{ $product->sku }}</code></td>
                                <td><span class="badge bg-secondary rounded-pill">{{ $product->unit }}</span></td>
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
                                <td colspan="5" class="text-center py-4 text-muted">
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
