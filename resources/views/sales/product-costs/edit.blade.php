@extends('layouts.app')

@section('title', 'Edit Product Pricing')

@section('content')
    <h1 class="mt-4">Edit Product Pricing</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/sales/product-costs') }}">Product Pricing</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i> Edit Pricing — {{ $productCost->product->name }}</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ url('/sales/product-costs/' . $productCost->id) }}">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Product</label>
                        <div class="form-control bg-light">{{ $productCost->product->name }}
                            ({{ $productCost->product->sku }})</div>
                    </div>
                    <div class="col-md-4">
                        <label for="production_cost" class="form-label">Production Cost (₹)</label>
                        <input type="number" step="0.01" class="form-control" id="production_cost"
                            name="production_cost" value="{{ old('production_cost', $productCost->production_cost) }}"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label for="selling_price" class="form-label">Selling Price (₹)</label>
                        <input type="number" step="0.01" class="form-control" id="selling_price" name="selling_price"
                            value="{{ old('selling_price', $productCost->selling_price) }}" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Profit / Unit</label>
                        <div class="form-control bg-light" id="profitPreview">₹0.00</div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Pricing</button>
                <a href="{{ url('/sales/product-costs') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function updateProfit() {
            const cost = parseFloat(document.getElementById('production_cost').value) || 0;
            const price = parseFloat(document.getElementById('selling_price').value) || 0;
            const profit = price - cost;
            document.getElementById('profitPreview').textContent = '₹' + profit.toFixed(2);
            document.getElementById('profitPreview').style.color = profit >= 0 ? 'green' : 'red';
        }
        document.getElementById('production_cost').addEventListener('input', updateProfit);
        document.getElementById('selling_price').addEventListener('input', updateProfit);
        updateProfit();
    </script>
@endsection
