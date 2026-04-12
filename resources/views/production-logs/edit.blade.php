@extends('layouts.app')

@section('title', 'Edit Production Log')

@section('content')
    <h1 class="mt-4">Edit Production Log</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/production') }}">Production Logs</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i> Edit Production Entry</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ url('/production/' . $productionLog->id) }}">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ old('product_id', $productionLog->product_id) == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="quantity_produced" class="form-label">Quantity Produced</label>
                        <input type="number" class="form-control" id="quantity_produced" name="quantity_produced"
                            value="{{ old('quantity_produced', $productionLog->quantity_produced) }}" min="1"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label for="production_date" class="form-label">Production Date</label>
                        <input type="date" class="form-control" id="production_date" name="production_date"
                            value="{{ old('production_date', $productionLog->production_date->format('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Updating this production log will reverse the previous material deductions and apply new ones based on
                    the updated quantity and product.
                </div>
                <button type="submit" class="btn btn-primary">Update Production Log</button>
                <a href="{{ url('/production') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
