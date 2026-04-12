@extends('layouts.app')

@section('title', 'Manage Companies')

@section('content')
    <h1 class="mt-4">Companies</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Companies</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-building me-1"></i> All Companies</span>
            <a href="{{ route('superadmin.companies.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i><span class="btn-label">Add Company</span>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Company Name</th>
                            <th class="col-hide-mobile">Business Type</th>
                            <th class="col-hide-mobile">Phone</th>
                            <th>Users</th>
                            <th>Plan</th>
                            <th class="col-hide-mobile">Expiry</th>
                            <th>Status</th>
                            <th style="min-width:200px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            <tr>
                                <td>{{ $loop->iteration + ($companies->currentPage() - 1) * $companies->perPage() }}</td>
                                <td class="fw-semibold">
                                    <a href="{{ url('/superadmin/companies/' . $company->id) }}"
                                        class="text-decoration-none">
                                        {{ $company->company_name }}
                                    </a>
                                </td>
                                <td class="col-hide-mobile">{{ ucfirst($company->business_type ?? '-') }}</td>
                                <td class="col-hide-mobile"><small>{{ $company->phone ?? '-' }}</small></td>
                                <td><span class="badge bg-secondary rounded-pill">{{ $company->users_count }}</span></td>
                                <td>
                                    <span
                                        class="badge bg-{{ $company->planColor() }} rounded-pill">{{ $company->planLabel() }}</span>
                                    @if ($company->isPlanExpired())
                                        <span class="badge bg-danger rounded-pill ms-1">Expired</span>
                                    @endif
                                </td>
                                <td class="col-hide-mobile">
                                    <small>{{ $company->plan_expires_at ? $company->plan_expires_at->format('Y-m-d') : '—' }}</small>
                                </td>
                                <td>
                                    @if ($company->status === 'active')
                                        <span class="badge bg-success rounded-pill">Active</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill">Blocked</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group">
                                        <form
                                            action="{{ url('/superadmin/companies/' . $company->id . '/toggle-status') }}"
                                            method="POST">
                                            @csrf @method('PATCH')
                                            @if ($company->status === 'active')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Block company" onclick="return confirm('Block this company?')">
                                                    <i class="fas fa-ban"></i>
                                                    <span class="btn-label ms-1">Block</span>
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-sm btn-outline-success"
                                                    title="Activate company"
                                                    onclick="return confirm('Activate this company?')">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span class="btn-label ms-1">Activate</span>
                                                </button>
                                            @endif
                                        </form>
                                        <a href="{{ route('superadmin.companies.show', $company) }}"
                                            class="btn btn-sm btn-outline-info" title="View details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('superadmin.companies.edit', $company) }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit company">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ route('superadmin.impersonate.start', $company) }}" method="POST"
                                            onsubmit="return confirm('Impersonate {{ addslashes($company->company_name) }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                title="Impersonate company">
                                                <i class="fas fa-user-secret"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('superadmin.companies.destroy', $company) }}" method="POST"
                                            onsubmit="return confirm('DELETE company \'{{ addslashes($company->company_name) }}\' and ALL its data? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Delete company">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-building fa-2x mb-2 d-block opacity-25"></i>No companies found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $companies->links() }}</div>
        </div>
    </div>
@endsection
