@extends('layouts.app')

@section('title', 'Raw Materials')

@section('content')
    <h1 class="mt-4">Raw Materials</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Raw Materials</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-cubes me-1"></i> Raw Materials List</div>
            <a href="{{ url('/materials/create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i><span class="btn-label">Add Material</span>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Name</th>
                            <th>Unit</th>
                            <th>Stock</th>
                            <th class="col-hide-mobile">Alert Level</th>
                            <th class="col-hide-mobile">Unit Cost</th>
                            <th>Status</th>
                            <th style="min-width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            <tr>
                                <td>{{ $loop->iteration + ($materials->currentPage() - 1) * $materials->perPage() }}</td>
                                <td class="fw-semibold">{{ $material->name }}</td>
                                <td><span class="badge bg-secondary rounded-pill">{{ $material->unit }}</span></td>
                                <td class="fw-bold">{{ $material->stock_qty }}</td>
                                <td class="col-hide-mobile">{{ $material->min_stock_alert }}</td>
                                <td class="col-hide-mobile">₹{{ number_format($material->unit_cost, 2) }}</td>
                                <td>
                                    @if ($material->stock_qty <= 0)
                                        <span class="badge bg-danger rounded-pill">Out of Stock</span>
                                    @elseif($material->stock_qty <= $material->min_stock_alert)
                                        <span class="badge bg-warning text-dark rounded-pill">Low Stock</span>
                                    @else
                                        <span class="badge bg-success rounded-pill">In Stock</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ url('/materials/' . $material->id . '/edit') }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit material">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ url('/materials/' . $material->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Delete material">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-cubes fa-2x mb-2 d-block opacity-25"></i>No raw materials found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $materials->links() }}</div>
        </div>
    </div>
@endsection
