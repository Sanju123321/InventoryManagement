@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <h1 class="mt-4">Edit User</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.users') }}">Users</a></li>
        <li class="breadcrumb-item active">{{ $user->name }}</li>
    </ol>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-user-edit me-2"></i>Edit User
                </div>
                <div class="card-body">
                    <form action="{{ route('superadmin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="company_id" class="form-label fw-semibold">Company <span
                                    class="text-danger">*</span></label>
                            <select name="company_id" id="company_id"
                                class="form-select @error('company_id') is-invalid @enderror" required>
                                <option value="" disabled>Select company</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                                        {{ $company->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-semibold">Full Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label fw-semibold">Phone</label>
                                <input type="text" name="phone_number" id="phone_number"
                                    class="form-control @error('phone_number') is-invalid @enderror"
                                    value="{{ old('phone_number', $user->phone_number) }}">
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email" name="email" id="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label fw-semibold">Role <span
                                        class="text-danger">*</span></label>
                                <select name="role" id="role"
                                    class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>
                                        Admin</option>
                                    <option value="inventory_admin"
                                        {{ old('role', $user->role) == 'inventory_admin' ? 'selected' : '' }}>Inventory
                                        Admin</option>
                                    <option value="sales_admin"
                                        {{ old('role', $user->role) == 'sales_admin' ? 'selected' : '' }}>Sales Admin
                                    </option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">Status <span
                                        class="text-danger">*</span></label>
                                <select name="status" id="status"
                                    class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active"
                                        {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="blocked"
                                        {{ old('status', $user->status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                            <a href="{{ route('superadmin.users') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-trash me-2"></i>Delete User
                </div>
                <div class="card-body">
                    <p class="text-danger fw-semibold mb-1">Warning: This action is irreversible!</p>
                    <p class="text-muted small mb-3">This user will be permanently removed from the system.</p>
                    <form action="{{ route('superadmin.users.destroy', $user) }}" method="POST"
                        onsubmit="return confirm('DELETE user \'{{ addslashes($user->name) }}\'? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
