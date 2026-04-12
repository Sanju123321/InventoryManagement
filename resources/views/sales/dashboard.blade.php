@extends('layouts.app')

@section('title', 'Sales Dashboard')

@section('styles')
    <style>
        /* ── Stat Cards ─────────────────────────────────────────── */
        .stat-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, .18) !important;
        }

        .stat-card .card-body {
            padding: 1.4rem 1.5rem;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-value {
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.1;
            margin: .3rem 0 .2rem;
        }

        .stat-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .7px;
            opacity: .85;
        }

        .stat-link {
            font-size: .78rem;
            opacity: .82;
            text-decoration: underline dotted;
        }

        /* ── Gradient presets ───────────────────────────────────── */
        .grad-green {
            background: linear-gradient(135deg, #0f9b58, #41d991);
        }

        .grad-blue {
            background: linear-gradient(135deg, #1a6fbf, #5bbcf8);
        }

        .grad-red {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
        }

        .grad-orange {
            background: linear-gradient(135deg, #e67e22, #f1c40f);
        }

        /* ── Section Cards ──────────────────────────────────────── */
        .sec-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, .06);
        }

        .sec-card .card-header {
            border-radius: 16px 16px 0 0 !important;
            background: #fff;
            font-weight: 600;
            padding: .9rem 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, .07);
        }

        .sec-card .card-body {
            padding: 0;
        }

        /* ── Tables ─────────────────────────────────────────────── */
        .sec-card .table th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6c757d;
            border-top: none;
        }

        .sec-card .table td,
        .sec-card .table th {
            padding: .65rem 1rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .status-pill {
            font-size: .72rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* ── Charts ─────────────────────────────────────────────── */
        .chart-box {
            position: relative;
            height: 240px;
        }

        /* ── Responsive tweaks ──────────────────────────────────── */
        @media (max-width: 575.98px) {
            .stat-value {
                font-size: 1.3rem;
            }

            .chart-box {
                height: 200px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 767.98px) {
            .table-scroll {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
@endsection

@section('content')

    {{-- Page Header --}}
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mt-4 mb-4">
        <div>
            <h1 class="mb-1 fw-bold">Sales Dashboard</h1>
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Sales Dashboard</li>
            </ol>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ url('/sales/orders/create') }}" class="btn btn-primary btn-sm px-3">
                <i class="fas fa-plus me-1"></i> New Order
            </a>
            <a href="{{ url('/sales/reports/products') }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fas fa-chart-bar me-1"></i> Reports
            </a>
        </div>
    </div>

    {{-- ── Stat Cards ──────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card grad-green text-white h-100">
                <div class="card-body d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="stat-label">Daily Income</div>
                        <div class="stat-value">₹{{ number_format($dailyIncome, 2) }}</div>
                        <div class="small stat-link text-white">Today</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card grad-blue text-white h-100">
                <div class="card-body d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="stat-label">Total Sales</div>
                        <div class="stat-value">₹{{ number_format($totalSales, 2) }}</div>
                        <a href="{{ url('/sales/orders') }}" class="small stat-link text-white">View Orders →</a>
                    </div>
                    <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card grad-red text-white h-100">
                <div class="card-body d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="stat-label">Pending Payments</div>
                        <div class="stat-value">₹{{ number_format($totalPending, 2) }}</div>
                        <div class="small stat-link text-white">Awaiting collection</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card grad-orange text-white h-100">
                <div class="card-body d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="stat-label">Pending Orders</div>
                        <div class="stat-value">{{ $pendingOrders }}<span class="fs-5 fw-normal opacity-75"> /
                                {{ $totalOrders }}</span></div>
                        <a href="{{ url('/sales/orders?status=pending') }}" class="small stat-link text-white">View Pending
                            →</a>
                    </div>
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Charts Row ──────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card sec-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-line me-2 text-primary"></i>Monthly Revenue — Last 6 Months</span>
                </div>
                <div class="card-body p-3">
                    <div class="chart-box"><canvas id="revenueChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card sec-card h-100">
                <div class="card-header">
                    <i class="fas fa-circle-half-stroke me-2 text-info"></i>Order Status Breakdown
                </div>
                <div class="card-body p-3 d-flex align-items-center justify-content-center">
                    <div class="chart-box w-100"><canvas id="statusChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Top Products + Recent Orders ───────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card sec-card">
                <div class="card-header">
                    <i class="fas fa-trophy me-2 text-warning"></i>Top Selling Products
                </div>
                <div class="card-body">
                    <div class="table-scroll">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:32px">#</th>
                                    <th>Product</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $i => $item)
                                    <tr>
                                        <td class="text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $item->product->name }}</td>
                                        <td><span class="badge bg-primary rounded-pill">{{ $item->total_qty }}</span></td>
                                        <td class="text-success fw-semibold">₹{{ number_format($item->total_revenue, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted fst-italic">No sales data yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card sec-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-invoice me-2 text-info"></i>Recent Orders</span>
                    <a href="{{ url('/sales/orders') }}" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-scroll">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td><a href="{{ url('/sales/orders/' . $order->id) }}"
                                                class="fw-semibold text-decoration-none">#{{ $order->id }}</a></td>
                                        <td>{{ $order->customer->name }}</td>
                                        <td>₹{{ number_format($order->total_amount, 2) }}</td>
                                        <td>
                                            @php
                                                $pill = match ($order->status) {
                                                    'pending' => 'bg-warning text-dark',
                                                    'approved' => 'bg-info text-white',
                                                    'rejected' => 'bg-danger',
                                                    'delivered' => 'bg-primary',
                                                    'paid' => 'bg-success',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span
                                                class="badge status-pill {{ $pill }}">{{ ucfirst($order->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted fst-italic">No orders yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Recent Payments ─────────────────────────────────────── --}}
    <div class="row g-3 mb-5">
        <div class="col-12">
            <div class="card sec-card">
                <div class="card-header">
                    <i class="fas fa-money-bill-wave me-2 text-success"></i>Recent Payments
                </div>
                <div class="card-body">
                    <div class="table-scroll">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                        <td><a href="{{ url('/sales/orders/' . $payment->sales_order_id) }}"
                                                class="fw-semibold text-decoration-none">#{{ $payment->sales_order_id }}</a>
                                        </td>
                                        <td>{{ $payment->salesOrder->customer->name ?? 'N/A' }}</td>
                                        <td class="text-success fw-semibold">₹{{ number_format($payment->amount, 2) }}
                                        </td>
                                        <td><span
                                                class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted fst-italic">No payments yet.
                                        </td>
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

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";

        /* Monthly Revenue Line Chart */
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: @json($monthlyLabels),
                datasets: [{
                    label: 'Revenue (₹)',
                    data: @json($monthlyRevenues),
                    borderColor: '#1a6fbf',
                    backgroundColor: 'rgba(26,111,191,.1)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#1a6fbf',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ₹' + ctx.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,.05)'
                        },
                        ticks: {
                            callback: v => '₹' + v.toLocaleString()
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        /* Order Status Doughnut Chart */
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: @json($statusLabels),
                datasets: [{
                    data: @json($statusCounts),
                    backgroundColor: ['#f39c12', '#3498db', '#27ae60', '#8e44ad', '#e74c3c', '#95a5a6'],
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 14,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection
