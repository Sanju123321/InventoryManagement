@extends('layouts.app')

@section('title', 'Edit Company')

@section('content')
    <h1 class="mt-4">Edit Company</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/superadmin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.companies') }}">Companies</a></li>
        <li class="breadcrumb-item active">{{ $company->company_name }}</li>
    </ol>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-edit me-2"></i>Edit Company
                </div>
                <div class="card-body">
                    <form action="{{ route('superadmin.companies.update', $company) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="company_name" class="form-label fw-semibold">Company Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="company_name" id="company_name"
                                class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name', $company->company_name) }}" required>
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="business_type" class="form-label fw-semibold">Business Type</label>
                            <select name="business_type" id="business_type"
                                class="form-select @error('business_type') is-invalid @enderror">
                                <option value="" disabled
                                    {{ old('business_type', $company->business_type) ? '' : 'selected' }}>Select type
                                </option>
                                <option value="textile"
                                    {{ old('business_type', $company->business_type) == 'textile' ? 'selected' : '' }}>
                                    Textile</option>
                                <option value="steel"
                                    {{ old('business_type', $company->business_type) == 'steel' ? 'selected' : '' }}>Steel
                                </option>
                                <option value="cosmetics"
                                    {{ old('business_type', $company->business_type) == 'cosmetics' ? 'selected' : '' }}>
                                    Cosmetics</option>
                                <option value="soap"
                                    {{ old('business_type', $company->business_type) == 'soap' ? 'selected' : '' }}>Soap
                                </option>
                                <option value="perfume"
                                    {{ old('business_type', $company->business_type) == 'perfume' ? 'selected' : '' }}>
                                    Perfume</option>
                                <option value="packaging"
                                    {{ old('business_type', $company->business_type) == 'packaging' ? 'selected' : '' }}>
                                    Packaging</option>
                                <option value="chemical"
                                    {{ old('business_type', $company->business_type) == 'chemical' ? 'selected' : '' }}>
                                    Chemical</option>
                                <option value="food"
                                    {{ old('business_type', $company->business_type) == 'food' ? 'selected' : '' }}>Food
                                    Processing</option>
                                <option value="other"
                                    {{ old('business_type', $company->business_type) == 'other' ? 'selected' : '' }}>Other
                                </option>
                            </select>
                            @error('business_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" id="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $company->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="status" class="form-label fw-semibold">Status <span
                                    class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active"
                                    {{ old('status', $company->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="blocked"
                                    {{ old('status', $company->status) === 'blocked' ? 'selected' : '' }}>Blocked</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                            <a href="{{ route('superadmin.companies') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-trash me-2"></i>Delete Company
                </div>
                <div class="card-body">
                    <p class="text-danger fw-semibold mb-1">Warning: This action is irreversible!</p>
                    <p class="text-muted small">Deleting this company will permanently remove all associated data including
                        products, raw materials, production logs, sales orders, and user accounts.</p>
                    <form action="{{ route('superadmin.companies.destroy', $company) }}" method="POST"
                        onsubmit="return confirm('DELETE company \'{{ addslashes($company->company_name) }}\' and ALL its data? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete Company
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
