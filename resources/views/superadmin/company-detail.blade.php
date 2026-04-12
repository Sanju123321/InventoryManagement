@extends('layouts.app')

@section('title', 'Company Details')

@section('content')
    <h1 class="mt-4">Company Details</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/companies') }}">Companies</a></li>
        <li class="breadcrumb-item active">{{ $company->company_name }}</li>
    </ol>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-building me-1"></i> Company Info</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tr>
                                <th>Company Name</th>
                                <td>{{ $company->company_name }}</td>
                            </tr>
                            <tr>
                                <th>Business Type</th>
                                <td>{{ ucfirst($company->business_type ?? '-') }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>{{ $company->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if ($company->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Blocked</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Plan</th>
                                <td>
                                    <span
                                        class="badge bg-{{ $company->planColor() }} fs-6">{{ $company->planLabel() }}</span>
                                    @if ($company->isPlanExpired())
                                        <span class="badge bg-danger ms-1">Expired</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Plan Expiry</th>
                                <td>
                                    @if ($company->plan_expires_at)
                                        {{ $company->plan_expires_at->format('d M Y') }}
                                        @if ($company->isPlanExpired())
                                            <span class="text-danger small ms-1">(Expired)</span>
                                        @elseif ($company->plan_expires_at->diffInDays(now()) <= 7)
                                            <span class="text-warning small ms-1">(Expiring soon)</span>
                                        @endif
                                    @else
                                        <span class="text-muted">No expiry</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created</th>
                                <td>{{ $company->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="p-3">
                        <form action="{{ url('/superadmin/companies/' . $company->id . '/toggle-status') }}" method="POST"
                            class="d-inline">
                            @csrf
                            @method('PATCH')
                            @if ($company->status === 'active')
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Block this company?')">
                                    <i class="fas fa-ban me-1"></i> Block Company
                                </button>
                            @else
                                <button type="submit" class="btn btn-success btn-sm"
                                    onclick="return confirm('Activate this company?')">
                                    <i class="fas fa-check me-1"></i> Activate Company
                                </button>
                            @endif
                        </form>

                        {{-- Impersonate button --}}
                        @if ($company->users->count() > 0)
                            <form action="{{ route('superadmin.impersonate.start', $company) }}" method="POST"
                                class="d-inline ms-2">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm"
                                    onclick="return confirm('Log in as {{ addslashes($company->company_name) }}\'s admin? You can stop impersonating at any time.')">
                                    <i class="fas fa-user-secret me-1"></i> Impersonate
                                </button>
                            </form>
                        @endif

                        {{-- Export button --}}
                        <a href="{{ route('superadmin.companies.export', $company) }}"
                            class="btn btn-secondary btn-sm ms-2">
                            <i class="fas fa-file-archive me-1"></i> Export Data
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-chart-bar me-1"></i> Statistics & Plan Usage</div>
                    <div class="card-body">
                        @php
                            $planCfg = $company->planConfig();
                            $userCount = $company->users->count();
                            $userMax = $planCfg['max_users'];
                            $prodPct =
                                $planCfg['max_products'] > 0
                                    ? min(100, round(($products / $planCfg['max_products']) * 100))
                                    : 0;
                            $matPct =
                                $planCfg['max_materials'] > 0
                                    ? min(100, round(($materials / $planCfg['max_materials']) * 100))
                                    : 0;
                            $userPct = $userMax > 0 ? min(100, round(($userCount / $userMax) * 100)) : 0;
                        @endphp

                        {{-- Users --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><i class="fas fa-users me-1"></i>Users</span>
                                <span>{{ $userCount }} / {{ $userMax === -1 ? '∞' : $userMax }}</span>
                            </div>
                            @if ($userMax !== -1)
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar bg-{{ $userPct >= 90 ? 'danger' : ($userPct >= 70 ? 'warning' : 'primary') }}"
                                        style="width:{{ $userPct }}%"></div>
                                </div>
                            @else
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar bg-success" style="width:100%"></div>
                                </div>
                            @endif
                        </div>

                        {{-- Products --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><i class="fas fa-box me-1"></i>Products</span>
                                <span>{{ $products }} /
                                    {{ $planCfg['max_products'] === -1 ? '∞' : $planCfg['max_products'] }}</span>
                            </div>
                            @if ($planCfg['max_products'] !== -1)
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar bg-{{ $prodPct >= 90 ? 'danger' : ($prodPct >= 70 ? 'warning' : 'success') }}"
                                        style="width:{{ $prodPct }}%"></div>
                                </div>
                            @else
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar bg-success" style="width:100%"></div>
                                </div>
                            @endif
                        </div>

                        {{-- Raw Materials --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><i class="fas fa-cubes me-1"></i>Raw Materials</span>
                                <span>{{ $materials }} /
                                    {{ $planCfg['max_materials'] === -1 ? '∞' : $planCfg['max_materials'] }}</span>
                            </div>
                            @if ($planCfg['max_materials'] !== -1)
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar bg-{{ $matPct >= 90 ? 'danger' : ($matPct >= 70 ? 'warning' : 'warning') }}"
                                        style="width:{{ $matPct }}%"></div>
                                </div>
                            @else
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar bg-success" style="width:100%"></div>
                                </div>
                            @endif
                        </div>

                        <table class="table table-bordered table-sm mt-3">
                            <tr>
                                <th>Production Logs</th>
                                <td>{{ $productions }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-users me-1"></i> Company Users</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($company->users as $user)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->phone_number ?? '-' }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($user->role) }}</span></td>
                                    <td>
                                        @if ($user->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Blocked</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No users in this company.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <a href="{{ url('/superadmin/companies') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>
            Back to
            Companies</a>
    @endsection
