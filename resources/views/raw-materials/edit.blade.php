@extends('layouts.app')

@section('title', 'Edit Raw Material')

@section('content')
    <h1 class="mt-4">Edit Raw Material</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/materials') }}">Raw Materials</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i> Edit Raw Material</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ url('/materials/' . $rawMaterial->id) }}">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label">Material Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name', $rawMaterial->name) }}" required maxlength="255">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
                        @php
                            $currentUnit = old('unit', $rawMaterial->custom_unit ? 'Other' : $rawMaterial->unit);
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
                            value="{{ old('custom_unit', $rawMaterial->custom_unit) }}"
                            style="display:{{ $currentUnit === 'Other' ? 'block' : 'none' }};">
                        @error('custom_unit')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="stock_qty" class="form-label">Stock Qty <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('stock_qty') is-invalid @enderror"
                            id="stock_qty" name="stock_qty" value="{{ old('stock_qty', $rawMaterial->stock_qty) }}"
                            min="0" required>
                        @error('stock_qty')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="min_stock_alert" class="form-label">Min Stock Alert Level</label>
                        <input type="number" step="0.01"
                            class="form-control @error('min_stock_alert') is-invalid @enderror" id="min_stock_alert"
                            name="min_stock_alert" value="{{ old('min_stock_alert', $rawMaterial->min_stock_alert) }}"
                            min="0">
                        @error('min_stock_alert')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="unit_cost" class="form-label">Unit Cost (₹)</label>
                        <input type="number" step="0.01" class="form-control @error('unit_cost') is-invalid @enderror"
                            id="unit_cost" name="unit_cost" value="{{ old('unit_cost', $rawMaterial->unit_cost) }}"
                            min="0">
                        @error('unit_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Material</button>
                <a href="{{ url('/materials') }}" class="btn btn-secondary">Cancel</a>
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
