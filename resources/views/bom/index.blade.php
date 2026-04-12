@extends('layouts.app')

@section('title', 'Bill of Materials')

@section('content')
    <h1 class="mt-4">Bill of Materials</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Bill of Materials</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-list-check me-1"></i> BOM Entries</div>
            <a href="{{ url('/bom/create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i><span class="btn-label">Add BOM Entry</span>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Product</th>
                            <th>Raw Material</th>
                            <th>Qty / Unit</th>
                            <th style="min-width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($boms as $bom)
                            <tr>
                                <td>{{ $loop->iteration + ($boms->currentPage() - 1) * $boms->perPage() }}</td>
                                <td class="fw-semibold">{{ $bom->product->name }}</td>
                                <td>{{ $bom->material->name }}
                                    <span class="badge bg-secondary rounded-pill ms-1">{{ $bom->material->unit }}</span>
                                </td>
                                <td><span class="fw-bold">{{ intval($bom->quantity_required) }}</span></td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ url('/bom/' . $bom->id . '/edit') }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit BOM">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ url('/bom/' . $bom->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete BOM">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-list-check fa-2x mb-2 d-block opacity-25"></i>No BOM entries found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $boms->links() }}</div>
        </div>
    </div>
@endsection
