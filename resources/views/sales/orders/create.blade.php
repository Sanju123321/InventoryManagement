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
                        <label for="firm_id" class="form-label">Firm <span class="text-danger">*</span></label>
                        <select class="form-control @error('firm_id') is-invalid @enderror" id="firm_id"
                            name="firm_id" required>
                            <option value="">Select Firm</option>
                            @foreach ($firms as $firm)
                                <option value="{{ $firm->id }}"
                                    {{ old('firm_id') == $firm->id ? 'selected' : '' }}>
                                    {{ $firm->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('firm_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
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
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div id="previousOrderBanner" class="alert alert-info mb-0 d-none">
                            <i class="fas fa-history"></i>
                            <span id="previousOrderText">Previous orders available.</span>
                        </div>
                    </div>
                </div>

                @include('sales.orders.partials.order-totals-fields')

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Order Items</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary d-none" id="loadPreviousOrder">
                        <i class="fas fa-history me-1"></i> Load Previous Items
                    </button>
                </div>

                <div id="previousOrdersPanel" class="d-none mb-4">
                    <div id="previousOrdersLoading" class="text-center text-muted py-3 d-none">
                        <i class="fas fa-spinner fa-spin"></i> Loading previous orders...
                    </div>
                    <div id="previousOrdersEmpty" class="alert alert-secondary d-none mb-0">No previous orders for
                        this customer.</div>
                    <div id="previousOrdersList"></div>
                </div>

                <p class="text-muted small mb-2" id="newOrderItemsLabel">New order lines</p>
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
                                        step="0.01" min="0.01" required></td>
                                <td><span class="line-total">0.00</span></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i
                                            class="fas fa-times"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end">Subtotal:</td>
                                <td><span id="displaySubtotal" class="fw-semibold">₹0.00</span></td>
                            </tr>
                            <tr id="gstRow">
                                <td colspan="4" class="text-end">GST (<span id="gstRateLabel">18</span>%):</td>
                                <td><span id="displayGst">₹0.00</span></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end">Discount:</td>
                                <td><span id="displayDiscount" class="text-danger">₹0.00</span></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end fw-bold fs-5">Grand Total:</td>
                                <td><span id="displayGrandTotal" class="fw-bold fs-5 text-primary">₹0.00</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" class="btn btn-secondary mb-3" id="addRow"><i class="fas fa-plus"></i> Add
                    Item</button>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> The order will be created with <strong>Pending</strong> status
                    for sales users. Admin-created orders are approved immediately.
                </div>

                <button type="submit" class="btn btn-primary">Create Order</button>
                <a href="{{ url('/sales/orders') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

@endsection

@section('scripts')
    @include('sales.orders.partials.order-totals-script')
    <script>
        let rowIndex = 1;
        let currentCustomerId = null;
        let cachedPreviousOrders = [];

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
            bindOrderItemEvents();
            recalculateOrderTotals();
        });

        bindOrderItemEvents();
        recalculateOrderTotals();

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
                <td><input type="number" name="items[IDX][price]" class="form-control price-input" step="0.01" min="0.01" required></td>
                <td><span class="line-total">0.00</span></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-times"></i></button></td>
            </tr>`;

        function loadItemsIntoForm(items, orderMeta) {
            const tbody = document.getElementById('itemsBody');
            tbody.innerHTML = '';
            rowIndex = 0;
            items.forEach(item => {
                const wrapper = document.createElement('tbody');
                wrapper.innerHTML = firstRowTemplate.replaceAll('IDX', rowIndex);
                const tr = wrapper.firstElementChild;
                const select = tr.querySelector('.product-select');
                for (const opt of select.options) {
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
            if (orderMeta) {
                if (orderMeta.gst_rate !== undefined) {
                    document.getElementById('gst_rate').value = String(orderMeta.gst_rate);
                }
                if (orderMeta.discount_amount !== undefined) {
                    document.getElementById('discount_amount').value = orderMeta.discount_amount;
                }
                if (orderMeta.notes) {
                    document.getElementById('notes').value = orderMeta.notes;
                }
            }
            bindOrderItemEvents();
            recalculateOrderTotals();
        }

        function gstLabel(rate) {
            if (rate === 0) return 'No GST';
            return 'GST ' + rate + '%';
        }

        function renderPreviousOrders(orders) {
            const list = document.getElementById('previousOrdersList');
            list.innerHTML = '';

            orders.forEach(order => {
                const card = document.createElement('div');
                card.className = 'card mb-3 border-secondary';

                let itemsHtml = '';
                if (order.items && order.items.length) {
                    itemsHtml = `
                        <table class="table table-sm table-bordered mb-0 mt-2">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Rate (₹)</th>
                                    <th class="text-end">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map(it => `
                                    <tr>
                                        <td>${it.product_name}</td>
                                        <td class="text-end">${it.quantity}</td>
                                        <td class="text-end">${Number(it.price).toFixed(2)}</td>
                                        <td class="text-end">${Number(it.total).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>`;
                } else {
                    itemsHtml = '<p class="text-muted small mb-0 mt-2">No line items.</p>';
                }

                card.innerHTML = `
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                        <div>
                            <strong>Order #${order.id}</strong>
                            <span class="text-muted ms-2">${order.date}</span>
                            <span class="badge bg-secondary ms-1">${order.status}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">${gstLabel(order.gst_rate)} · Discount ₹${Number(order.discount_amount).toFixed(2)} · Total <strong>₹${Number(order.total_amount).toFixed(2)}</strong></span>
                            <button type="button" class="btn btn-sm btn-primary use-previous-order" data-order-id="${order.id}">Use this order</button>
                        </div>
                    </div>
                    <div class="card-body py-2">${itemsHtml}</div>
                `;
                list.appendChild(card);
            });

            list.querySelectorAll('.use-previous-order').forEach(btn => {
                btn.addEventListener('click', function() {
                    const order = cachedPreviousOrders.find(o => o.id == this.dataset.orderId);
                    if (!order) return;
                    loadItemsIntoForm(order.items || [], {
                        gst_rate: order.gst_rate,
                        discount_amount: order.discount_amount,
                        notes: order.notes
                    });
                    document.getElementById('previousOrdersPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    const newTable = document.getElementById('itemsTable');
                    if (newTable) newTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        }

        function fetchAndShowPreviousOrders() {
            if (!currentCustomerId) return;

            const panel = document.getElementById('previousOrdersPanel');
            const loading = document.getElementById('previousOrdersLoading');
            const empty = document.getElementById('previousOrdersEmpty');
            const list = document.getElementById('previousOrdersList');

            panel.classList.remove('d-none');
            loading.classList.remove('d-none');
            empty.classList.add('d-none');
            list.innerHTML = '';

            fetch(`{{ route('sales.orders.previous-orders') }}?customer_id=${currentCustomerId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(orders => {
                    loading.classList.add('d-none');
                    cachedPreviousOrders = orders;
                    if (!orders.length) {
                        empty.classList.remove('d-none');
                        return;
                    }
                    renderPreviousOrders(orders);
                })
                .catch(() => {
                    loading.classList.add('d-none');
                    empty.textContent = 'Failed to load previous orders.';
                    empty.classList.remove('d-none');
                });
        }

        document.getElementById('customer_id').addEventListener('change', function() {
            currentCustomerId = this.value;
            cachedPreviousOrders = [];
            const banner = document.getElementById('previousOrderBanner');
            const loadBtn = document.getElementById('loadPreviousOrder');
            const panel = document.getElementById('previousOrdersPanel');

            banner.classList.add('d-none');
            loadBtn.classList.add('d-none');
            panel.classList.add('d-none');
            document.getElementById('previousOrdersList').innerHTML = '';

            if (!currentCustomerId) return;

            fetch(`{{ route('sales.orders.previous-orders') }}?customer_id=${currentCustomerId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(orders => {
                    if (orders.length > 0) {
                        cachedPreviousOrders = orders;
                        document.getElementById('previousOrderText').textContent =
                            `${orders.length} previous order(s) found. Click "Load Previous Items" below.`;
                        banner.classList.remove('d-none');
                        loadBtn.classList.remove('d-none');
                    }
                })
                .catch(() => {});
        });

        document.getElementById('loadPreviousOrder').addEventListener('click', function() {
            if (document.getElementById('previousOrdersPanel').classList.contains('d-none')) {
                fetchAndShowPreviousOrders();
                this.innerHTML = '<i class="fas fa-chevron-up me-1"></i> Hide Previous Orders';
            } else {
                document.getElementById('previousOrdersPanel').classList.add('d-none');
                this.innerHTML = '<i class="fas fa-history me-1"></i> Load Previous Items';
            }
        });

        if (document.getElementById('customer_id').value) {
            document.getElementById('customer_id').dispatchEvent(new Event('change'));
        }
    </script>
@endsection
