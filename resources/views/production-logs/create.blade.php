@extends('layouts.app')

@section('title', 'Log Production')

@section('content')
    <h1 class="mt-4">Log Production</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/production') }}">Production Logs</a></li>
        <li class="breadcrumb-item active">Log Production</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus me-1"></i> New Production Entry</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ url('/production') }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}
                                    ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="quantity_produced" class="form-label">Quantity Produced</label>
                        <input type="number" class="form-control" id="quantity_produced" name="quantity_produced"
                            value="{{ old('quantity_produced') }}" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label for="production_date" class="form-label">Production Date</label>
                        <input type="date" class="form-control" id="production_date" name="production_date"
                            value="{{ old('production_date', date('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    When production is logged, the system will automatically deduct raw materials from stock based on the
                    Bill of Materials (BOM) for the selected product.
                </div>
                <button type="submit" class="btn btn-primary">Log Production</button>
                <a href="{{ url('/production') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
