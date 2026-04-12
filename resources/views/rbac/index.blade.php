@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="container-fluid px-4">

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1 text-gray-800">User Management</h1>
                <p class="text-muted mb-0">Manage sub-users and their roles within your company.</p>
            </div>
            @if (Auth::user()->isAdmin())
                <a href="{{ route('users.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i> Add User
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Current user card --}}
        <div class="card border-start border-primary border-4 shadow-sm mb-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                    style="width:48px;height:48px;font-size:1.2rem;">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div>
                    <div class="fw-bold">{{ Auth::user()->name }} <span
                            class="badge {{ Auth::user()->roleBadgeClass() }} ms-1">{{ Auth::user()->roleLabel() }}</span>
                    </div>
                    <div class="text-muted small">{{ Auth::user()->email }}</div>
                </div>
                <div class="ms-auto text-muted small"><i class="fas fa-circle-check text-success me-1"></i>You (current
                    session)</div>
            </div>
        </div>

        {{-- Sub-users table --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-primary"></i>Team Members</h6>
                <span class="badge bg-secondary">{{ $users->count() }} user{{ $users->count() === 1 ? '' : 's' }}</span>
            </div>
            <div class="card-body p-0">
                @if ($users->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">No sub-users yet. <a href="{{ route('users.create') }}">Add one</a>.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">User</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Permissions</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                                    style="width:38px;height:38px;font-size:.9rem;flex-shrink:0;">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $user->name }}</div>
                                                    <div class="text-muted small">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $user->roleBadgeClass() }}">{{ $user->roleLabel() }}</span>
                                        </td>
                                        <td>
                                            @if ($user->status === 'active')
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                            @else
                                                <span
                                                    class="badge bg-danger-subtle text-danger border border-danger-subtle">Blocked</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php $perms = $user->rolePermissions(); @endphp
                                            @foreach ($perms as $perm)
                                                <span
                                                    class="badge bg-light text-secondary border me-1">{{ $perm->name }}</span>
                                            @endforeach
                                        </td>
                                        <td class="text-end pe-4">
                                            @if (Auth::user()->isAdmin())
                                                <a href="{{ route('users.edit', $user) }}"
                                                    class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Remove this user from the company?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Role reference card --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-shield-halved me-2 text-primary"></i>Role Permissions Reference
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($roles as $role)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-bold mb-2">{{ $role->name }}</div>
                                <ul class="list-unstyled mb-0 small text-muted">
                                    @foreach ($role->permissions as $perm)
                                        <li><i class="fas fa-check text-success me-1"></i>{{ $perm->name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
@endsection
