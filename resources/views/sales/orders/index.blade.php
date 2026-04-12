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
                <div class="col-12 col-sm-6 col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach (['pending', 'approved', 'rejected', 'delivered', 'paid'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All Customers</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}"
                        placeholder="From">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}"
                        placeholder="To">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ url('/sales/orders') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-file-invoice me-1"></i> Orders</div>
            <div class="d-flex gap-2">
                <a href="{{ route('sales.orders.export', request()->query()) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i><span class="btn-label">Export CSV</span>
                </a>
                <a href="{{ url('/sales/orders/create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i><span class="btn-label">New Order</span>
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th class="col-hide-mobile">Paid</th>
                            <th class="col-hide-mobile">Pending</th>
                            <th>Status</th>
                            <th class="col-hide-mobile">Created By</th>
                            <th class="col-hide-mobile">Date</th>
                            <th style="min-width:60px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td><span class="badge bg-dark rounded-pill">#{{ $order->id }}</span></td>
                                <td class="fw-semibold">{{ $order->customer->name }}</td>
                                <td class="fw-bold">₹{{ number_format($order->total_amount, 2) }}</td>
                                <td class="col-hide-mobile text-success">₹{{ number_format($order->paid_amount, 2) }}</td>
                                <td class="col-hide-mobile text-danger">₹{{ number_format($order->pending_amount, 2) }}
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($order->status) {
                                            'pending' => 'bg-warning text-dark',
                                            'approved' => 'bg-info',
                                            'rejected' => 'bg-danger',
                                            'delivered' => 'bg-primary',
                                            'paid' => 'bg-success',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span
                                        class="badge {{ $badgeClass }} rounded-pill">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td class="col-hide-mobile"><small>{{ $order->creator->name ?? '-' }}</small></td>
                                <td class="col-hide-mobile"><small>{{ $order->created_at->format('d-m-Y') }}</small></td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ url('/sales/orders/' . $order->id) }}"
                                            class="btn btn-sm btn-outline-info" title="View order">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-file-invoice fa-2x mb-2 d-block opacity-25"></i>No orders found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $orders->links() }}</div>
        </div>
    </div>
@endsection
