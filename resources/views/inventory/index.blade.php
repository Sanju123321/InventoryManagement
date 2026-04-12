@extends('layouts.app')

@section('title', 'Inventory Overview')

@section('content')
    <h1 class="mt-4">Inventory Overview</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Inventory</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3 d-flex gap-2">
        <a href="{{ url('/inventory/create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Record Transaction
        </a>
        <a href="{{ route('inventory.export') }}" class="btn btn-success btn-sm">
            <i class="fas fa-file-csv me-1"></i> Export CSV
        </a>
    </div>

    <!-- Raw Material Stock Summary -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-cubes me-1"></i> Raw Material Stock Summary
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Material Name</th>
                            <th>Unit</th>
                            <th>Current Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $material->name }}</td>
                                <td><span class="badge bg-secondary rounded-pill">{{ $material->unit }}</span></td>
                                <td class="fw-bold">{{ $material->stock_qty }}</td>
                                <td>
                                    @if ($material->stock_qty <= 0)
                                        <span class="badge bg-danger rounded-pill">Out of Stock</span>
                                    @elseif($material->stock_qty <= $material->min_stock_alert)
                                        <span class="badge bg-warning text-dark rounded-pill">Low Stock</span>
                                    @else
                                        <span class="badge bg-success rounded-pill">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No raw materials found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Inventory Transactions -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-exchange-alt me-1"></i> Recent Inventory Transactions
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Material</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th class="col-hide-mobile">Stock After</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            <tr>
                                <td>{{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}
                                </td>
                                <td class="fw-semibold">{{ $txn->material->name }}</td>
                                <td>
                                    @if ($txn->type === 'in')
                                        <span class="badge bg-success rounded-pill">Stock In</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill">Stock Out</span>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $txn->quantity }}</td>
                                <td class="col-hide-mobile">{{ $txn->stock_after }}</td>
                                <td><small>{{ $txn->created_at->format('Y-m-d') }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No inventory transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $transactions->links() }}</div>
        </div>
    </div>
@endsection
