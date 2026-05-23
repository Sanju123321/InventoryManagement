@extends('layouts.app')

@section('title', 'Product Categories')

@section('content')
    <h1 class="mt-4">Product Categories</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/products') }}">Products</a></li>
        <li class="breadcrumb-item active">Categories</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-tags me-1"></i> Categories</div>
            <a href="{{ url('/product-categories/create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Add Category
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Name</th>
                            <th>Products</th>
                            <th style="min-width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                                <td class="fw-semibold">{{ $category->name }}</td>
                                <td><span class="badge bg-secondary">{{ $category->products_count }}</span></td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ url('/product-categories/' . $category->id . '/edit') }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ url('/product-categories/' . $category->id) }}" method="POST"
                                            onsubmit="return confirm('Delete this category?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $categories->links() }}</div>
        </div>
    </div>
@endsection
