@extends('layouts.app')

@section('title', 'Add Customer')

@section('content')
    <h1 class="mt-4">Add Customer</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/customers') }}">Customers</a></li>
        <li class="breadcrumb-item active">Add</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><i class="fas fa-plus me-1"></i> New Customer</div>
            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importCustomersModal">
                <i class="fas fa-file-import me-1"></i><span class="btn-label">Import CSV</span>
            </button>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ url('/customers') }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}"
                            required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                            value="{{ old('phone') }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="{{ old('email') }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="1">{{ old('address') }}</textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    @include('customers.partials.google-location-field')
                </div>
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <label for="contact_details" class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control @error('contact_details') is-invalid @enderror" id="contact_details" name="contact_details"
                            value="{{ old('contact_details') }}" required maxlength="10" pattern="[0-9]{10}" placeholder="10-digit mobile number">
                        @error('contact_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="gst_number" class="form-label">GST Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('gst_number') is-invalid @enderror" id="gst_number" name="gst_number"
                            value="{{ old('gst_number') }}" required placeholder="e.g. 22AAAAA0000A1Z5">
                        @error('gst_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <label for="authorized_person" class="form-label">Authorized Person</label>
                        <input type="text" class="form-control @error('authorized_person') is-invalid @enderror" id="authorized_person" name="authorized_person"
                            value="{{ old('authorized_person') }}">
                        @error('authorized_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="md_details" class="form-label">MD Details <span class="text-muted small">(optional)</span></label>
                        <textarea class="form-control @error('md_details') is-invalid @enderror" id="md_details" name="md_details" rows="2">{{ old('md_details') }}</textarea>
                        @error('md_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Customer</button>
                <a href="{{ url('/customers') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <div class="modal fade" id="importCustomersModal" tabindex="-1" aria-labelledby="importCustomersModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importCustomersModalLabel">Import Customers (CSV)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Use a <strong>.csv</strong> file (not Excel .xlsx). Columns:
                            <code>company_id</code>, <code>created_by</code>, <code>name</code>, <code>phone</code>,
                            <code>email</code>, <code>address</code>, <code>google_location</code>, <code>state</code>,
                            <code>authorized_person</code>, <code>contact_details</code>, <code>gst_number</code>,
                            <code>md_details</code>.
                            If blank, <code>company_id</code> / <code>created_by</code> default to your account.
                        </p>
                        <div class="mb-3">
                            <label for="customer_csv_file" class="form-label">CSV File</label>
                            <input type="file" class="form-control" id="customer_csv_file" name="csv_file" accept=".csv,text/csv" required>
                        </div>
                        <a href="{{ route('customers.import.template') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download me-1"></i>Download template
                        </a>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-import me-1"></i>Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('customers.partials.google-location-scripts')
@endsection
