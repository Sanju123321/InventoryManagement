@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <h1 class="mt-4">Edit Product</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/products') }}">Products</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i> Edit Product</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ url('/products/' . $product->id) }}">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name', $product->name) }}" required maxlength="255">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku"
                            name="sku" value="{{ old('sku', $product->sku) }}" required maxlength="100">
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
                        @php
                            $currentUnit = old('unit', $product->custom_unit ? 'Other' : $product->unit);
                        @endphp
                        <select class="form-control @error('unit') is-invalid @enderror" id="unit" name="unit"
                            required>
                            <option value="">Select Unit</option>
                            @foreach (config('units') as $key => $label)
                                <option value="{{ $key }}" {{ $currentUnit === $key ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                            <option value="Other" {{ $currentUnit === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <input type="text" class="form-control mt-2 @error('custom_unit') is-invalid @enderror"
                            id="custom_unit" name="custom_unit" placeholder="Enter custom unit"
                            value="{{ old('custom_unit', $product->custom_unit) }}"
                            style="display:{{ $currentUnit === 'Other' ? 'block' : 'none' }};">
                        @error('custom_unit')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="{{ url('/products') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('unit').addEventListener('change', function() {
            let customField = document.getElementById('custom_unit');
            if (this.value === 'Other') {
                customField.style.display = 'block';
                customField.required = true;
            } else {
                customField.style.display = 'none';
                customField.required = false;
                customField.value = '';
            }
        });
    </script>
@endsection
