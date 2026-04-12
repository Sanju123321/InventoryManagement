@extends('layouts.app')

@section('title', 'Create Sales Order')

@section('content')
    <h1 class="mt-4">Create Sales Order</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/sales/orders') }}">Sales Orders</a></li>
        <li class="breadcrumb-item active">Create</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus me-1"></i> New Sales Order</div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ url('/sales/orders') }}" id="orderForm">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select class="form-control" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div id="previousOrderBanner" class="alert alert-info w-100 mb-0 d-none">
                            <i class="fas fa-history"></i>
                            <span id="previousOrderText">This customer has a previous order.</span>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="loadPreviousOrder">
                                Load Previous Items
                            </button>
                        </div>
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
                            <tr class="item-row">
                                <td>
                                    <select name="items[0][product_id]" class="form-control product-select" required>
                                        <option value="">Select Product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                data-price="{{ $productCosts[$product->id] ?? 0 }}">
                                                {{ $product->name }} ({{ $product->sku }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="items[0][quantity]" class="form-control qty-input"
                                        min="1" value="1" required></td>
                                <td><input type="number" name="items[0][price]" class="form-control price-input"
                                        step="0.01" min="0" required></td>
                                <td><span class="line-total">0.00</span></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i
                                            class="fas fa-times"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                <td><span id="grandTotal" class="fw-bold">₹0.00</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" class="btn btn-secondary mb-3" id="addRow"><i class="fas fa-plus"></i> Add
                    Item</button>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> The order will be created with <strong>Pending</strong> status.
                    It
                    needs to be approved before stock is deducted.
                </div>

                <button type="submit" class="btn btn-primary">Create Order</button>
                <a href="{{ url('/sales/orders') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let rowIndex = 1;

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

        // Product costs map for the current product options
        const productCosts = @json($productCosts);

        // ── Recent order items per customer ──────────────────────────────
        let previousItems = [];

        document.getElementById('customer_id').addEventListener('change', function() {
            const customerId = this.value;
            const banner = document.getElementById('previousOrderBanner');
            previousItems = [];
            banner.classList.add('d-none');
            if (!customerId) return;

            fetch(`{{ url('/sales/orders/recent-items') }}?customer_id=${customerId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(items => {
                    if (items.length > 0) {
                        previousItems = items;
                        document.getElementById('previousOrderText').textContent =
                            `Previous order had ${items.length} item(s). Load them?`;
                        banner.classList.remove('d-none');
                    }
                })
                .catch(() => {});
        });

        document.getElementById('loadPreviousOrder').addEventListener('click', function() {
            if (!previousItems.length) return;
            const tbody = document.getElementById('itemsBody');
            tbody.innerHTML = '';
            rowIndex = 0;
            const firstRowTemplate = `
                <tr class="item-row">
                    <td>
                        <select name="items[IDX][product_id]" class="form-control product-select" required>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $productCosts[$product->id] ?? 0 }}">{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="items[IDX][quantity]" class="form-control qty-input" min="1" value="1" required></td>
                    <td><input type="number" name="items[IDX][price]" class="form-control price-input" step="0.01" min="0" required></td>
                    <td><span class="line-total">0.00</span></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-times"></i></button></td>
                </tr>`;
            previousItems.forEach(item => {
                const row = document.createElement('tbody');
                row.innerHTML = firstRowTemplate.replaceAll('IDX', rowIndex);
                const tr = row.firstElementChild;
                const select = tr.querySelector('.product-select');
                for (let opt of select.options) {
                    if (opt.value == item.product_id) {
                        opt.selected = true;
                        break;
                    }
                }
                tr.querySelector('.qty-input').value = item.quantity;
                tr.querySelector('.price-input').value = item.price;
                tbody.appendChild(tr);
                rowIndex++;
            });
            bindEvents();
            recalculate();
            document.getElementById('previousOrderBanner').classList.add('d-none');
        });
    </script>
@endsection
