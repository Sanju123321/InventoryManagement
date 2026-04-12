@extends('layouts.app')

@section('title', 'Edit BOM Entry')

@section('content')
    <h1 class="mt-4">Edit BOM Entry</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/bom') }}">Bill of Materials</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i> Edit BOM Entry</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ url('/bom/' . $bom->id) }}" id="bomForm">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ old('product_id', $bom->product_id) == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Select Raw Materials & Quantities</label>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="selectAll" title="Select All">
                                    </th>
                                    <th>Raw Material</th>
                                    <th>Unit</th>
                                    <th style="width: 200px;">Qty Required (per unit product)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($materials as $material)
                                    @php
                                        $isSelected = isset($existingBoms[$material->id]);
                                        $existingQty = $isSelected ? $existingBoms[$material->id] : '';
                                    @endphp
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="material-check" data-id="{{ $material->id }}"
                                                {{ old("materials.{$material->id}.material_id", $isSelected ? '1' : '') ? 'checked' : '' }}>
                                        </td>
                                        <td>{{ $material->name }}</td>
                                        <td>{{ $material->unit }}</td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm qty-input"
                                                name="materials[{{ $material->id }}][quantity_required]"
                                                id="qty_{{ $material->id }}" min="0.001" step="any"
                                                data-unit="{{ strtolower($material->unit) }}"
                                                value="{{ old("materials.{$material->id}.quantity_required", $existingQty) }}"
                                                {{ old("materials.{$material->id}.material_id", $isSelected ? '1' : '') ? '' : 'disabled' }}>
                                            <input type="hidden" name="materials[{{ $material->id }}][material_id]"
                                                value="{{ $material->id }}" id="mid_{{ $material->id }}"
                                                {{ old("materials.{$material->id}.material_id", $isSelected ? '1' : '') ? '' : 'disabled' }}>
                                            <small class="text-muted gram-hint" id="hint_{{ $material->id }}"></small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update BOM Entry</button>
                <a href="{{ url('/bom') }}" class="btn btn-secondary">Cancel</a>
            </form>

            <script>
                document.querySelectorAll('.material-check').forEach(function(cb) {
                    cb.addEventListener('change', function() {
                        var id = this.dataset.id;
                        var qty = document.getElementById('qty_' + id);
                        var mid = document.getElementById('mid_' + id);
                        qty.disabled = !this.checked;
                        mid.disabled = !this.checked;
                        if (!this.checked) {
                            qty.value = '';
                        }
                    });
                });
                document.getElementById('selectAll').addEventListener('change', function() {
                    var checked = this.checked;
                    document.querySelectorAll('.material-check').forEach(function(cb) {
                        cb.checked = checked;
                        cb.dispatchEvent(new Event('change'));
                    });
                });
                function updateGramHint(input) {
                    var unit = input.dataset.unit;
                    var val = parseFloat(input.value);
                    var id = input.id.replace('qty_', '');
                    var hint = document.getElementById('hint_' + id);
                    if (!hint) return;
                    if (!input.value || isNaN(val) || val <= 0) { hint.textContent = ''; return; }
                    if (unit === 'kg' && val < 1) {
                        hint.textContent = '= ' + (val * 1000).toFixed(2).replace(/\.?0+$/, '') + ' grams';
                    } else if (unit === 'g' && val < 1) {
                        hint.textContent = '= ' + (val * 1000).toFixed(2).replace(/\.?0+$/, '') + ' mg';
                    } else if (unit === 'l' && val < 1) {
                        hint.textContent = '= ' + (val * 1000).toFixed(2).replace(/\.?0+$/, '') + ' ml';
                    } else {
                        hint.textContent = '';
                    }
                }
                document.querySelectorAll('.qty-input').forEach(function(input) {
                    input.addEventListener('input', function() { updateGramHint(this); });
                    updateGramHint(input);
                });

                document.getElementById('bomForm').addEventListener('submit', function(e) {
                    var checked = document.querySelectorAll('.material-check:checked');
                    if (checked.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one raw material.');
                        return;
                    }
                    var valid = true;
                    checked.forEach(function(cb) {
                        var qty = document.getElementById('qty_' + cb.dataset.id);
                        if (!qty.value || parseFloat(qty.value) <= 0) {
                            valid = false;
                            qty.focus();
                        }
                    });
                    if (!valid) {
                        e.preventDefault();
                        alert('Please enter quantity for all selected materials.');
                    }
                });
            </script>
        </div>
    </div>
@endsection
