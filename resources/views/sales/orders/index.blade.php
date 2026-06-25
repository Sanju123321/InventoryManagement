@extends('layouts.app')

@section('title', 'Sales Orders')

@section('content')
    <h1 class="mt-4">Sales Orders</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Sales Orders</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filters</div>
        <div class="card-body">
            <form method="GET" action="{{ url('/sales/orders') }}" class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small text-muted mb-1 d-lg-none">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach (['pending', 'approved', 'rejected', 'dispatched', 'paid'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ $s === 'dispatched' ? 'Dispatched' : ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small text-muted mb-1 d-lg-none">Customer</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All Customers</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small text-muted mb-1 d-lg-none">Firm</label>
                    <select name="firm_id" class="form-select form-select-sm">
                        <option value="">All Firms</option>
                        @foreach ($firms as $firm)
                            <option value="{{ $firm->id }}"
                                {{ request('firm_id') == $firm->id ? 'selected' : '' }}>{{ $firm->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small text-muted mb-1 d-lg-none">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}"
                        placeholder="From">
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small text-muted mb-1 d-lg-none">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}"
                        placeholder="To">
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ url('/sales/orders') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><i class="fas fa-file-invoice me-1"></i> Orders</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('sales.orders.export', request()->query()) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i><span class="btn-label">Export CSV</span>
                </a>
                <a href="{{ url('/sales/orders/create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i><span class="btn-label">New Order</span>
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            {{-- Desktop / tablet table --}}
            <div class="table-responsive d-none d-xl-block">
                <table class="table table-hover table-striped table-sm mb-0 align-middle sales-orders-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Order #</th>
                            <th>Firm</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Pending</th>
                            <th>Status</th>
                            <th>Receiving Ok</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th class="text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @include('sales.orders.partials.order-row', ['order' => $order, 'layout' => 'table'])
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="fas fa-file-invoice fa-2x mb-2 d-block opacity-25"></i>No orders found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Medium screens: compact table --}}
            <div class="table-responsive d-none d-md-block d-xl-none">
                <table class="table table-hover table-striped table-sm mb-0 align-middle sales-orders-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Order #</th>
                            <th>Firm</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Receiving Ok</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @include('sales.orders.partials.order-row', ['order' => $order, 'layout' => 'compact'])
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="d-md-none sales-orders-mobile p-3">
                @forelse($orders as $order)
                    @include('sales.orders.partials.order-row', ['order' => $order, 'layout' => 'card'])
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-file-invoice fa-2x mb-2 d-block opacity-25"></i>No orders found.
                    </div>
                @endforelse
            </div>

            <div class="p-3">{{ $orders->links() }}</div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .sales-orders-mobile .order-card {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: .5rem;
            background: #fff;
        }

        .sales-orders-mobile .order-card + .order-card {
            margin-top: .75rem;
        }

        .sales-orders-mobile .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .5rem;
            padding: .75rem .85rem .5rem;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        .sales-orders-mobile .order-card-body {
            padding: .65rem .85rem;
        }

        .sales-orders-mobile .order-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .5rem .75rem;
        }

        .sales-orders-mobile .order-meta-item label {
            display: block;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #6c757d;
            margin-bottom: .1rem;
        }

        .sales-orders-mobile .order-meta-item span {
            font-size: .84rem;
            font-weight: 600;
        }

        .sales-orders-mobile .order-card-actions {
            display: flex;
            gap: .5rem;
            padding: .65rem .85rem .75rem;
            border-top: 1px solid rgba(0, 0, 0, .06);
        }

        .sales-orders-mobile .order-card-actions .btn,
        .sales-orders-mobile .order-card-actions form {
            flex: 1;
        }

        .sales-orders-mobile .order-card-actions .btn {
            width: 100%;
        }

        @media (max-width: 575.98px) {
            .sales-orders-mobile .order-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
