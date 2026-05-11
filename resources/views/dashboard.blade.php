@extends('layouts.app')

@section('title', 'Dashboard - Kemtex ERP')

@section('content')
    <h1 class="mt-4">Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
    <div class="row">
        <div class="col-6 col-xl-3">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Total Products</div>
                            <div class="fs-4 fw-bold">{{ $totalProducts ?? 0 }}</div>
                        </div>
                        <i class="fas fa-box fa-2x"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ url('/products') }}">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Raw Materials</div>
                            <div class="fs-4 fw-bold">{{ $totalMaterials ?? 0 }}</div>
                        </div>
                        <i class="fas fa-cubes fa-2x"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ url('/materials') }}">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Production Today</div>
                            <div class="fs-4 fw-bold">{{ $productionToday ?? 0 }}</div>
                        </div>
                        <i class="fas fa-industry fa-2x"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ url('/production') }}">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Low Stock Alerts</div>
                            <div class="fs-4 fw-bold">{{ $lowStockCount ?? 0 }}</div>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ url('/reports/low-stock') }}">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-industry me-1"></i>
                    Recent Production Logs
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty Produced</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentProduction ?? [] as $log)
                                    <tr>
                                        <td>{{ $log->product->name }}</td>
                                        <td>{{ $log->quantity_produced }}</td>
                                        <td>{{ $log->production_date->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No production logs yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Low Stock Materials
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Unit</th>
                                    <th>Stock Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockMaterials ?? [] as $material)
                                    <tr>
                                        <td>{{ $material->name }}</td>
                                        <td>{{ $material->unit }}</td>
                                        <td><span class="badge bg-danger">{{ $material->stock_qty }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No low stock alerts.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-bar me-1"></i> Product Sales & Profit Report</span>
                    <a href="{{ url('/sales/reports/products') }}" class="btn btn-sm btn-outline-primary">View Full
                        Report</a>
                </div>
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
                                @forelse($productReport ?? [] as $item)
                                    <tr>
                                        <td>{{ $item['product']->name }}</td>
                                        <td>{{ $item['product']->sku }}</td>
                                        <td>₹{{ $item['cost'] ? number_format($item['cost']->production_cost, 2) : '-' }}
                                        </td>
                                        <td>₹{{ $item['cost'] ? number_format($item['cost']->selling_price, 2) : '-' }}
                                        </td>
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
                            @if (collect($productReport ?? [])->count() > 0)
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="5">Totals</td>
                                        <td>{{ collect($productReport)->sum('total_sold') }}</td>
                                        <td></td>
                                        <td>₹{{ number_format(collect($productReport)->sum('total_revenue'), 2) }}</td>
                                        <td>₹{{ number_format(collect($productReport)->sum('total_profit'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exchange-alt me-1"></i>
                    Recent Inventory Transactions
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Stock After</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions ?? [] as $txn)
                                    <tr>
                                        <td>{{ $txn->material->name }}</td>
                                        <td>
                                            @if ($txn->type === 'in')
                                                <span class="badge bg-success">In</span>
                                            @else
                                                <span class="badge bg-danger">Out</span>
                                            @endif
                                        </td>
                                        <td>{{ $txn->quantity }}</td>
                                        <td>{{ $txn->stock_after }}</td>
                                        <td>{{ $txn->created_at->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No transactions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
