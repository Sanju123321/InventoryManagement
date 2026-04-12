@extends('layouts.app')

@section('title', 'Cross-Company Analytics')

@section('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
@endsection

@section('content')
    <h1 class="mt-4">Cross-Company Analytics</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Analytics</li>
    </ol>

    {{-- ── KPI Cards ──────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary fs-4">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Companies</div>
                        <div class="fs-4 fw-bold">{{ number_format($kpi['total_companies']) }}</div>
                        <div class="small text-success">{{ $kpi['active_companies'] }} active</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success fs-4">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Total Revenue</div>
                        <div class="fs-4 fw-bold">₹{{ number_format($kpi['total_revenue'], 0) }}</div>
                        <div class="small text-warning">₹{{ number_format($kpi['pending_revenue'], 0) }} pending</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info fs-4">
                        <i class="fas fa-industry"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Total Production</div>
                        <div class="fs-4 fw-bold">{{ number_format($kpi['total_production']) }}</div>
                        <div class="small text-muted">units produced (all time)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-danger h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 text-danger fs-4">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Low Stock Items</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($kpi['total_low_stock']) }}</div>
                        <div class="small text-muted">across all tenants</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-secondary bg-opacity-10 p-3 text-secondary fs-4">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase fw-semibold">Total Orders</div>
                        <div class="fs-4 fw-bold">{{ number_format($kpi['total_orders']) }}</div>
                        <div class="small text-primary">{{ $kpi['orders_this_month'] }} this month</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 1: Production (daily) + Revenue trend ──────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-industry me-1 text-info"></i>
                    Daily Production – Last 30 Days (All Companies)
                </div>
                <div class="card-body">
                    <canvas id="productionDailyChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-chart-line me-1 text-success"></i>
                    Revenue Trend – Last 6 Months
                </div>
                <div class="card-body">
                    <canvas id="revenueTrendChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 2: Top companies revenue + Top companies production ──────── --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-trophy me-1 text-warning"></i>
                    Top Companies by Revenue
                </div>
                <div class="card-body">
                    @if (count($revenueLabels))
                        <canvas id="revenueBarChart" height="220"></canvas>
                    @else
                        <p class="text-muted text-center py-4">No approved / delivered orders yet.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-boxes me-1 text-info"></i>
                    Top Companies by Production Volume
                </div>
                <div class="card-body">
                    @if (count($prodCompanyLabels))
                        <canvas id="productionCompanyChart" height="220"></canvas>
                    @else
                        <p class="text-muted text-center py-4">No production logs yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 3: Low-stock by company chart + Low-stock table ────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-exclamation-circle me-1 text-danger"></i>
                    Low Stock Alerts by Company
                </div>
                <div class="card-body">
                    @if (count($lowStockLabels))
                        <canvas id="lowStockChart" height="260"></canvas>
                    @else
                        <p class="text-success text-center py-4"><i class="fas fa-check-circle me-1"></i>No low-stock items
                            across any tenant.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-1 text-danger"></i> Low-Stock Materials (All Tenants)</span>
                    <span class="badge bg-danger">{{ $lowStockMaterials->count() }} items</span>
                </div>
                <div class="card-body p-0" style="max-height:380px;overflow-y:auto;">
                    @if ($lowStockMaterials->isEmpty())
                        <p class="text-success text-center py-4 m-0"><i class="fas fa-check-circle me-1"></i>All materials
                            are well-stocked.</p>
                    @else
                        <table class="table table-sm table-bordered table-striped mb-0">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th>Company</th>
                                    <th>Material</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-end">Min Alert</th>
                                    <th>Unit</th>
                                    <th>Gap</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lowStockMaterials as $m)
                                    @php $gap = $m->min_stock_alert - $m->stock_qty; @endphp
                                    <tr class="{{ $m->stock_qty == 0 ? 'table-danger' : 'table-warning' }}">
                                        <td class="small fw-semibold">{{ $m->company_name }}</td>
                                        <td class="small">{{ $m->name }}</td>
                                        <td class="text-end small {{ $m->stock_qty == 0 ? 'text-danger fw-bold' : '' }}">
                                            {{ number_format($m->stock_qty, 2) }}
                                        </td>
                                        <td class="text-end small text-muted">{{ number_format($m->min_stock_alert, 2) }}
                                        </td>
                                        <td class="small">{{ $m->unit }}</td>
                                        <td class="small">
                                            <span class="badge bg-danger">-{{ number_format($gap, 2) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

@section('scripts')
    <script>
        // ── Helpers ──────────────────────────────────────────────
        const COLORS = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#858796', '#5a5c69', '#f8f9fc', '#6610f2', '#fd7e14'
        ];

        function randomColors(n) {
            return Array.from({
                length: n
            }, (_, i) => COLORS[i % COLORS.length]);
        }

        // ── 1. Daily Production Line Chart ───────────────────────
        const ctx1 = document.getElementById('productionDailyChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: @json($productionLabels),
                    datasets: [{
                        label: 'Units Produced',
                        data: @json($productionData),
                        borderColor: '#36b9cc',
                        backgroundColor: 'rgba(54,185,204,0.1)',
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // ── 2. Revenue Trend Line Chart ───────────────────────────
        const ctx2 = document.getElementById('revenueTrendChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: @json($trendLabels),
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: @json($trendData),
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28,200,138,0.1)',
                        borderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: v => '₹' + v.toLocaleString('en-IN')
                            }
                        }
                    }
                }
            });
        }

        // ── 3. Top Companies by Revenue Bar ───────────────────────
        const ctx3 = document.getElementById('revenueBarChart');
        if (ctx3) {
            const labels3 = @json($revenueLabels);
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: labels3,
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: @json($revenueData),
                        backgroundColor: randomColors(labels3.length),
                        borderRadius: 4,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: v => '₹' + v.toLocaleString('en-IN')
                            }
                        }
                    }
                }
            });
        }

        // ── 4. Top Companies by Production Bar ───────────────────
        const ctx4 = document.getElementById('productionCompanyChart');
        if (ctx4) {
            const labels4 = @json($prodCompanyLabels);
            new Chart(ctx4, {
                type: 'bar',
                data: {
                    labels: labels4,
                    datasets: [{
                        label: 'Units Produced',
                        data: @json($prodCompanyData),
                        backgroundColor: randomColors(labels4.length),
                        borderRadius: 4,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // ── 5. Low Stock Doughnut ─────────────────────────────────
        const ctx5 = document.getElementById('lowStockChart');
        if (ctx5) {
            const labels5 = @json($lowStockLabels);
            new Chart(ctx5, {
                type: 'doughnut',
                data: {
                    labels: labels5,
                    datasets: [{
                        data: @json($lowStockData),
                        backgroundColor: randomColors(labels5.length),
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
@endsection
@endsection
