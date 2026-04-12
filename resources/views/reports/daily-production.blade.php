@extends('layouts.app')

@section('title', 'Daily Production Report')

@section('content')
    <h1 class="mt-4">Daily Production Report</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/reports') }}">Reports</a></li>
        <li class="breadcrumb-item active">Daily Production</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i> Filter by Date
        </div>
        <div class="card-body">
            <form method="GET" action="{{ url('/reports/daily-production') }}" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-day me-1"></i> Production on {{ $date }}
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Quantity Produced</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $log->product->name }}</td>
                                <td>{{ $log->product->sku }}</td>
                                <td>{{ $log->quantity_produced }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No production recorded for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($logs->count())
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th>{{ $logs->sum('quantity_produced') }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <a href="{{ url('/reports') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Reports</a>
@endsection
