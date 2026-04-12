@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
    <h1 class="mt-4">Users</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Users</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users me-1"></i> All Users</span>
            <a href="{{ route('superadmin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus me-1"></i><span class="btn-label">Add User</span>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th class="col-hide-mobile">Phone</th>
                            <th>Company</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="min-width:130px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                                <td class="fw-semibold">{{ $user->name }}</td>
                                <td><small>{{ $user->email }}</small></td>
                                <td class="col-hide-mobile">{{ $user->phone_number ?? '-' }}</td>
                                <td>{{ $user->company->company_name ?? '-' }}</td>
                                <td>
                                    <span class="badge rounded-pill"
                                        style="background:{{ match ($user->role) {'admin' => '#0d6efd','inventory_admin' => '#6610f2','sales_admin' => '#d63384',default => '#0dcaf0'} }};color:#fff;">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($user->status === 'active')
                                        <span class="badge bg-success rounded-pill">Active</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill">Blocked</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group">
                                        <form action="{{ url('/superadmin/users/' . $user->id . '/toggle-status') }}"
                                            method="POST">
                                            @csrf @method('PATCH')
                                            @if ($user->status === 'active')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Block user" onclick="return confirm('Block this user?')">
                                                    <i class="fas fa-ban"></i>
                                                    <span class="btn-label ms-1">Block</span>
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-sm btn-outline-success"
                                                    title="Activate user" onclick="return confirm('Activate this user?')">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span class="btn-label ms-1">Activate</span>
                                                </button>
                                            @endif
                                        </form>
                                        <a href="{{ route('superadmin.users.edit', $user) }}"
                                            class="btn btn-sm btn-outline-warning" title="Edit user">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                        <form action="{{ route('superadmin.users.destroy', $user) }}" method="POST"
                                            onsubmit="return confirm('DELETE user \'{{ addslashes($user->name) }}\'?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Delete user">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-users fa-2x mb-2 d-block opacity-25"></i>No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $users->links() }}</div>
        </div>
    </div>
@endsection
