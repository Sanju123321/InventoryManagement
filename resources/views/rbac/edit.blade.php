@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="container-fluid px-4">

        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="h3 mb-0 text-gray-800">Edit User</h1>
                <p class="text-muted mb-0 small">Update role and details for {{ $user->name }}.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-7">

                {{-- Edit details --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="fas fa-user-edit me-2 text-primary"></i>User Details
                    </div>
                    <div class="card-body p-4">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <p class="mb-1 small">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('users.update', $user) }}">
                            @csrf @method('PUT')

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}" required maxlength="255">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Role</label>
                                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->slug }}"
                                                {{ old('role', $user->role) === $role->slug ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="status" class="form-select @error('status') is-invalid @enderror"
                                        required>
                                        <option value="active"
                                            {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="blocked"
                                            {{ old('status', $user->status) === 'blocked' ? 'selected' : '' }}>Blocked
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-1"></i> Save Changes
                                </button>
                                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>

                    </div>
                </div>

                {{-- Reset password --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="fas fa-key me-2 text-warning"></i>Reset Password
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('users.reset-password', $user) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">New Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="8">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirm Password</label>
                                    <input type="password" name="password_confirmation" class="form-control" required
                                        minlength="8">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-1"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection
