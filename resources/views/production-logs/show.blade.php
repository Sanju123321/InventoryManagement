@extends('layouts.app')

@section('title', 'Production Log Details')

@section('content')
    <h1 class="mt-4">Production Log Details</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/production') }}">Production Logs</a></li>
        <li class="breadcrumb-item active">Details</li>
    </ol>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-industry me-1"></i> Production Summary</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tr>
                                <th>Product</th>
                                <td>{{ $productionLog->product->name }}</td>
                            </tr>
                            <tr>
                                <th>SKU</th>
                                <td>{{ $productionLog->product->sku }}</td>
                            </tr>
                            <tr>
                                <th>Quantity Produced</th>
                                <td>{{ $productionLog->quantity_produced }}</td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td>{{ $productionLog->production_date->format('Y-m-d') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white"><i class="fas fa-calculator me-1"></i> Production Cost</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tr>
                                <th>Total Material Cost</th>
                                <td class="fw-bold">₹{{ number_format($totalCost, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Cost Per Unit</th>
                                <td class="fw-bold">₹{{ number_format($costPerUnit, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-cubes me-1"></i> Materials Used & Cost Breakdown</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Material</th>
                                    <th>Qty Used</th>
                                    <th>Unit</th>
                                    <th>Unit Cost</th>
                                    <th>Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materialCosts as $mc)
                                    <tr>
                                        <td>{{ $mc['material'] }}</td>
                                        <td>{{ $mc['qty_used'] }}</td>
                                        <td>{{ $mc['unit'] }}</td>
                                        <td>₹{{ number_format($mc['unit_cost'], 2) }}</td>
                                        <td>₹{{ number_format($mc['cost'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No material cost data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (count($materialCosts))
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Total</th>
                                        <th>₹{{ number_format($totalCost, 2) }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="{{ url('/production') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to
        Production Logs</a>
@endsection
