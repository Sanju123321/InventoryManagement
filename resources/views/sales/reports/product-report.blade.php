@extends('layouts.app')

@section('title', 'Product Report')

@section('content')
    <h1 class="mt-4">Product Report</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Product Report</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-chart-bar me-1"></i> Product Sales & Profit Report</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Production Cost</th>
                            <th>Selling Price</th>
                            <th>Total Produced</th>
                            <th>Total Sold</th>
                            <th>Available Stock</th>
                            <th>Total Revenue</th>
                            <th>Total Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $item)
                            <tr>
                                <td>{{ $item['product']->name }}</td>
                                <td>{{ $item['product']->sku }}</td>
                                <td>₹{{ $item['cost'] ? number_format($item['cost']->production_cost, 2) : '-' }}</td>
                                <td>₹{{ $item['cost'] ? number_format($item['cost']->selling_price, 2) : '-' }}</td>
                                <td>{{ $item['total_produced'] }}</td>
                                <td>{{ $item['total_sold'] }}</td>
                                <td>
                                    @if ($item['available_stock'] <= 0)
                                        <span class="badge bg-danger">{{ $item['available_stock'] }}</span>
                                    @elseif($item['available_stock'] <= 10)
                                        <span class="badge bg-warning">{{ $item['available_stock'] }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $item['available_stock'] }}</span>
                                    @endif
                                </td>
                                <td>₹{{ number_format($item['total_revenue'], 2) }}</td>
                                <td>
                                    @if ($item['total_profit'] >= 0)
                                        <span
                                            class="text-success fw-bold">₹{{ number_format($item['total_profit'], 2) }}</span>
                                    @else
                                        <span
                                            class="text-danger fw-bold">-₹{{ number_format(abs($item['total_profit']), 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($products->count() > 0)
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="5">Totals</td>
                                <td>{{ $products->sum('total_sold') }}</td>
                                <td></td>
                                <td>₹{{ number_format($products->sum('total_revenue'), 2) }}</td>
                                <td>₹{{ number_format($products->sum('total_profit'), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
