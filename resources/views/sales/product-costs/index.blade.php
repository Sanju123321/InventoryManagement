@extends('layouts.app')

@section('title', 'Product Pricing')

@section('content')
    <h1 class="mt-4">Product Pricing</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Product Pricing</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-tags me-1"></i> Product Cost & Pricing</div>
            <div class="d-flex gap-2">
                <a href="{{ route('sales.product-costs.export') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <a href="{{ url('/sales/product-costs/create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Set Pricing
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Production Cost</th>
                            <th>Selling Price</th>
                            <th>Profit / Unit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($costs as $cost)
                            <tr>
                                <td>{{ $loop->iteration + ($costs->currentPage() - 1) * $costs->perPage() }}</td>
                                <td>{{ $cost->product->name }}</td>
                                <td>₹{{ number_format($cost->production_cost, 2) }}</td>
                                <td>₹{{ number_format($cost->selling_price, 2) }}</td>
                                <td>
                                    @if ($cost->profit >= 0)
                                        <span class="text-success fw-bold">₹{{ number_format($cost->profit, 2) }}</span>
                                    @else
                                        <span
                                            class="text-danger fw-bold">-₹{{ number_format(abs($cost->profit), 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ url('/sales/product-costs/' . $cost->id . '/edit') }}"
                                        class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No pricing set. Add product pricing to calculate
                                    profit.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $costs->links() }}</div>
        </div>
    </div>
@endsection
