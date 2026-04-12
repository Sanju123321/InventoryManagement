@extends('layouts.app')

@section('title', 'Production History Report')

@section('content')
    <h1 class="mt-4">Production History</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/reports') }}">Reports</a></li>
        <li class="breadcrumb-item active">Production History</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i> Filter by Date Range
        </div>
        <div class="card-body">
            <form method="GET" action="{{ url('/reports/production-history') }}" class="row g-3 align-items-end">
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
            <i class="fas fa-history me-1"></i> Production Records ({{ $from }} to {{ $to }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Quantity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $loop->iteration + ($logs->currentPage() - 1) * $logs->perPage() }}</td>
                                <td>{{ $log->production_date->format('Y-m-d') }}</td>
                                <td>{{ $log->product->name }}</td>
                                <td>{{ $log->product->sku }}</td>
                                <td>{{ $log->quantity_produced }}</td>
                                <td>
                                    <a href="{{ url('/production/' . $log->id) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No production records found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $logs->links() }}</div>
        </div>
    </div>

    <a href="{{ url('/reports') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Reports</a>
@endsection
