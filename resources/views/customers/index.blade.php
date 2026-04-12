@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <h1 class="mt-4">Customers</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Customers</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-users me-1"></i> Customers List</div>
            <div class="d-flex gap-2">
                <a href="{{ route('customers.export') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i><span class="btn-label">Export CSV</span>
                </a>
                <a href="{{ url('/customers/create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i><span class="btn-label">Add Customer</span>
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Name</th>
                            <th class="col-hide-mobile">Phone</th>
                            <th class="col-hide-mobile">Email</th>
                            @if(auth()->user()->role !== 'sales_admin')
                                <th class="col-hide-mobile">Added By</th>
                            @endif
                            <th style="min-width:120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
                                <td class="fw-semibold">{{ $customer->name }}</td>
                                <td class="col-hide-mobile">{{ $customer->phone ?? '-' }}</td>
                                <td class="col-hide-mobile"><small>{{ $customer->email ?? '-' }}</small></td>
                                @if(auth()->user()->role !== 'sales_admin')
                                    <td class="col-hide-mobile">
                                        @if($customer->creator)
                                            <span class="badge bg-secondary">{{ $customer->creator->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <div class="action-group">
                                        <a href="{{ url('/customers/' . $customer->id) }}"
                                            class="btn btn-sm btn-outline-info" title="Customer ledger">
                                            <i class="fas fa-book"></i>
                                        </a>
                                        <a href="{{ url('/customers/' . $customer->id . '/edit') }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit customer">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ url('/customers/' . $customer->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Delete customer">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role !== 'sales_admin' ? 6 : 5 }}" class="text-center py-4 text-muted">
                                    <i class="fas fa-users fa-2x mb-2 d-block opacity-25"></i>No customers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $customers->links() }}</div>
        </div>
    </div>
@endsection
