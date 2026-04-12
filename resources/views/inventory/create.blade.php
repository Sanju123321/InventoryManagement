@extends('layouts.app')

@section('title', 'Record Inventory Transaction')

@section('content')
    <h1 class="mt-4">Record Inventory Transaction</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/inventory') }}">Inventory</a></li>
        <li class="breadcrumb-item active">New Transaction</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-exchange-alt me-1"></i> Stock In / Stock Out
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ url('/inventory') }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="material_id" class="form-label">Raw Material</label>
                        <select class="form-select" id="material_id" name="material_id" required>
                            <option value="" disabled selected>Select material</option>
                            @foreach ($materials as $material)
                                <option value="{{ $material->id }}"
                                    {{ old('material_id') == $material->id ? 'selected' : '' }}>
                                    {{ $material->name }} ({{ $material->unit }} — Stock: {{ $material->stock_qty }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="type" class="form-label">Transaction Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>Stock In (Received)</option>
                            <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>Stock Out (Used/Issued)
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity"
                            value="{{ old('quantity') }}" step="0.01" min="0.01" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Record Transaction</button>
                <a href="{{ url('/inventory') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
