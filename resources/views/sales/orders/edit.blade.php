@extends('layouts.app')

@section('title', 'Edit Sales Order')

@section('content')
    <h1 class="mt-4">Edit Sales Order #{{ $order->id }}</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/sales/orders') }}">Sales Orders</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/sales/orders/' . $order->id) }}">Order #{{ $order->id }}</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-pen me-1"></i> Update Order</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('sales.orders.update', $order) }}" id="orderForm">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select class="form-control" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id', $order->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h5>Order Items</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="itemsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price (₹)</th>
                                <th>Total (₹)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @foreach (old('items', $order->items->toArray()) as $index => $item)
                                @php
                                    $productId = is_array($item) ? $item['product_id'] : $item->product_id;
                                    $quantity = is_array($item) ? $item['quantity'] : $item->quantity;
                                    $price = is_array($item) ? $item['price'] : $item->price;
                                    $lineTotal = is_array($item)
                                        ? ($item['quantity'] ?? 0) * ($item['price'] ?? 0)
                                        : $item->total;
                                @endphp
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $index }}][product_id]"
                                            class="form-control product-select" required>
                                            <option value="">Select Product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}"
                                                    data-price="{{ $productCosts[$product->id] ?? 0 }}"
                                                    {{ $productId == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} ({{ $product->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][quantity]"
                                            class="form-control qty-input" min="1" value="{{ $quantity }}"
                                            required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][price]"
                                            class="form-control price-input" step="0.01" min="0.01"
                                            value="{{ $price }}" required>
                                    </td>
                                    <td><span class="line-total">{{ number_format($lineTotal, 2) }}</span></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-row"><i
                                                class="fas fa-times"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                <td><span id="grandTotal"
                                        class="fw-bold">₹{{ number_format($order->total_amount, 2) }}</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" class="btn btn-secondary mb-3" id="addRow"><i class="fas fa-plus"></i> Add
                    Item</button>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Admin can update the customer and any order item. Totals will be
                    recalculated automatically.
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ url('/sales/orders/' . $order->id) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let rowIndex = {{ $order->items->count() }};

        function recalculate() {
            let grandTotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const total = qty * price;
                row.querySelector('.line-total').textContent = total.toFixed(2);
                grandTotal += total;
            });
            document.getElementById('grandTotal').textContent = '₹' + grandTotal.toFixed(2);
        }

        document.getElementById('addRow').addEventListener('click', function() {
            const tbody = document.getElementById('itemsBody');
            const firstRow = tbody.querySelector('.item-row');
            const newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll('select, input').forEach(el => {
                el.name = el.name.replace(/\[\d+\]/, '[' + rowIndex + ']');
                if (el.tagName === 'SELECT') el.selectedIndex = 0;
                if (el.tagName === 'INPUT') {
                    if (el.classList.contains('qty-input')) el.value = 1;
                    else el.value = '';
                }
            });
            newRow.querySelector('.line-total').textContent = '0.00';

            tbody.appendChild(newRow);
            rowIndex++;
            bindEvents();
            recalculate();
        });

        function bindEvents() {
            document.querySelectorAll('.remove-row').forEach(btn => {
                btn.onclick = function() {
                    if (document.querySelectorAll('.item-row').length > 1) {
                        this.closest('.item-row').remove();
                        recalculate();
                    }
                };
            });

            document.querySelectorAll('.product-select').forEach(select => {
                select.onchange = function() {
                    const option = this.options[this.selectedIndex];
                    const price = option.getAttribute('data-price') || 0;
                    this.closest('.item-row').querySelector('.price-input').value = price;
                    recalculate();
                };
            });

            document.querySelectorAll('.qty-input, .price-input').forEach(input => {
                input.oninput = recalculate;
            });
        }

        bindEvents();
        recalculate();
    </script>
@endsection
