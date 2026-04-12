@extends('layouts.app')

@section('title', 'Create Company')

@section('content')
    <h1 class="mt-4">Create Company</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies') }}">Companies</a></li>
        <li class="breadcrumb-item active">Create</li>
    </ol>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-building me-2"></i>New Company
                </div>
                <div class="card-body">
                    <form action="{{ route('superadmin.companies.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="company_name" class="form-label fw-semibold">Company Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="company_name" id="company_name"
                                class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name') }}" required>
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="business_type" class="form-label fw-semibold">Business Type</label>
                            <select name="business_type" id="business_type"
                                class="form-select @error('business_type') is-invalid @enderror">
                                <option value="" disabled {{ old('business_type') ? '' : 'selected' }}>Select type
                                </option>
                                <option value="textile" {{ old('business_type') == 'textile' ? 'selected' : '' }}>Textile
                                </option>
                                <option value="steel" {{ old('business_type') == 'steel' ? 'selected' : '' }}>Steel
                                </option>
                                <option value="cosmetics" {{ old('business_type') == 'cosmetics' ? 'selected' : '' }}>
                                    Cosmetics</option>
                                <option value="soap" {{ old('business_type') == 'soap' ? 'selected' : '' }}>Soap</option>
                                <option value="perfume" {{ old('business_type') == 'perfume' ? 'selected' : '' }}>Perfume
                                </option>
                                <option value="packaging" {{ old('business_type') == 'packaging' ? 'selected' : '' }}>
                                    Packaging</option>
                                <option value="chemical" {{ old('business_type') == 'chemical' ? 'selected' : '' }}>
                                    Chemical</option>
                                <option value="food" {{ old('business_type') == 'food' ? 'selected' : '' }}>Food
                                    Processing</option>
                                <option value="other" {{ old('business_type') == 'other' ? 'selected' : '' }}>Other
                                </option>
                            </select>
                            @error('business_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" id="phone"
                                class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="status" class="form-label fw-semibold">Status <span
                                    class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="blocked" {{ old('status') === 'blocked' ? 'selected' : '' }}>Blocked
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create Company
                            </button>
                            <a href="{{ route('superadmin.companies') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
