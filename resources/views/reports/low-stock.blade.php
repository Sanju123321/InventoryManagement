@extends('layouts.app')

@section('title', 'Low Stock Report')

@section('content')
    <h1 class="mt-4">Low Stock Report</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/reports') }}">Reports</a></li>
        <li class="breadcrumb-item active">Low Stock</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-exclamation-triangle me-1"></i> Materials Below Alert Threshold
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Material Name</th>
                            <th>Unit</th>
                            <th>Current Stock</th>
                            <th>Alert Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $material->name }}</td>
                                <td>{{ $material->unit }}</td>
                                <td>{{ $material->stock_qty }}</td>
                                <td>{{ $material->min_stock_alert }}</td>
                                <td>
                                    @if ($material->stock_qty <= 0)
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-success">
                                    <i class="fas fa-check-circle me-1"></i> All materials are adequately stocked.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <a href="{{ url('/reports') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Reports</a>
@endsection
