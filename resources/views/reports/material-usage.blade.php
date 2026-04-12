@extends('layouts.app')

@section('title', 'Material Usage Report')

@section('content')
    <h1 class="mt-4">Material Usage Report</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/reports') }}">Reports</a></li>
        <li class="breadcrumb-item active">Material Usage</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i> Filter by Date Range
        </div>
        <div class="card-body">
            <form method="GET" action="{{ url('/reports/material-usage') }}" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label for="from" class="form-label">From</label>
                    <input type="date" class="form-control" id="from" name="from" value="{{ $from }}">
                </div>
                <div class="col-auto">
                    <label for="to" class="form-label">To</label>
                    <input type="date" class="form-control" id="to" name="to" value="{{ $to }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-cubes me-1"></i> Material Consumption ({{ $from }} to {{ $to }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Material</th>
                            <th>Unit</th>
                            <th>Total Used</th>
                            <th>Transactions</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usage as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item['material']->name }}</td>
                                <td>{{ $item['material']->unit }}</td>
                                <td>{{ $item['total_used'] }}</td>
                                <td>{{ $item['transactions'] }}</td>
                                <td>
                                    @if ($item['material']->stock_qty <= 10)
                                        <span class="badge bg-danger">{{ $item['material']->stock_qty }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $item['material']->stock_qty }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No material usage found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <a href="{{ url('/reports') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Reports</a>
@endsection
