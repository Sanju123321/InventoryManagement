@extends('layouts.app')

@section('title', 'Profile Settings')

@section('content')
    <h1 class="mt-4">Profile Settings</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Profile Settings</li>
    </ol>

    <div class="row">
        {{-- ── Update Profile ────────────────────────────────────────────── --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-user-edit me-2"></i>Update Profile
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" id="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone_number" id="phone_number"
                                class="form-control @error('phone_number') is-invalid @enderror"
                                value="{{ old('phone_number', $user->phone_number) }}">
                            @error('phone_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Role</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ ucfirst(str_replace('_', ' ', $user->role)) }}" disabled>
                        </div>

                        @if ($user->company)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Company</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $user->company->company_name }}" disabled>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Change Password ───────────────────────────────────────────── --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-lock me-2"></i>Change Password
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Current Password</label>
                            <input type="password" name="current_password" id="current_password"
                                class="form-control @error('current_password') is-invalid @enderror" required
                                autocomplete="current-password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">New Password</label>
                            <input type="password" name="password" id="password"
                                class="form-control @error('password') is-invalid @enderror" required
                                autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control" required autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="fas fa-key me-1"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>

            {{-- ── Account Info ────────────────────────────────────────────── --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <i class="fas fa-info-circle me-2"></i>Account Information
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0 small">
                        <tr>
                            <th class="text-muted" style="width:40%">Member Since</th>
                            <td>{{ $user->created_at->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Last Updated</th>
                            <td>{{ $user->updated_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                <span class="badge bg-{{ $user->status === 'active' ? 'success' : 'danger' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
