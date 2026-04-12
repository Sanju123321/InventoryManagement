@extends('layouts.app')

@section('title', 'Add Customer')

@section('content')
    <h1 class="mt-4">Add Customer</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/customers') }}">Customers</a></li>
        <li class="breadcrumb-item active">Add</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus me-1"></i> New Customer</div>
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
                    <div class="col-md-6">
                        <label for="name" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                            value="{{ old('phone') }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="{{ old('email') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="1">{{ old('address') }}</textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="contact_details" class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control @error('contact_details') is-invalid @enderror" id="contact_details" name="contact_details"
                            value="{{ old('contact_details') }}" required maxlength="10" pattern="[0-9]{10}" placeholder="10-digit mobile number">
                        @error('contact_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="gst_number" class="form-label">GST Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('gst_number') is-invalid @enderror" id="gst_number" name="gst_number"
                            value="{{ old('gst_number') }}" required placeholder="e.g. 22AAAAA0000A1Z5">
                        @error('gst_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="authorized_person" class="form-label">Authorized Person</label>
                        <input type="text" class="form-control @error('authorized_person') is-invalid @enderror" id="authorized_person" name="authorized_person"
                            value="{{ old('authorized_person') }}">
                        @error('authorized_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
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
@endsection
