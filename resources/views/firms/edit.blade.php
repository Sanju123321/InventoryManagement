@extends('layouts.app')

@section('title', 'Edit Firm')

@section('content')
    <h1 class="mt-4">Edit Firm</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('firms.index') }}">Firms</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i> Edit Firm</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('firms.update', $firm) }}">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name', $firm->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="gst" class="form-label">GST</label>
                        <input type="text" class="form-control @error('gst') is-invalid @enderror" id="gst"
                            name="gst" value="{{ old('gst', $firm->gst) }}" placeholder="e.g. 22AAAAA0000A1Z5">
                        @error('gst')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <label for="mobile_number" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control @error('mobile_number') is-invalid @enderror"
                            id="mobile_number" name="mobile_number"
                            value="{{ old('mobile_number', $firm->mobile_number) }}" maxlength="20">
                        @error('mobile_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control @error('status') is-invalid @enderror" id="status" name="status"
                            required>
                            <option value="active"
                                {{ old('status', $firm->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive"
                                {{ old('status', $firm->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address"
                        rows="3">{{ old('address', $firm->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Update Firm</button>
                <a href="{{ route('firms.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
