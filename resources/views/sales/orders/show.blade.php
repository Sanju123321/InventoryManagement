@extends('layouts.app')

@section('title', 'Order #' . $order->id)

@section('content')
    <h1 class="mt-4">Order #{{ $order->id }}</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/sales/orders') }}">Sales Orders</a></li>
        <li class="breadcrumb-item active">Order #{{ $order->id }}</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><i class="fas fa-info-circle me-1"></i> Order Details</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Customer</th>
                            <td>{{ $order->customer->name }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @php
                                    $badgeClass = match ($order->status) {
                                        'pending' => 'bg-warning text-dark',
                                        'approved' => 'bg-info',
                                        'rejected' => 'bg-danger',
                                        'delivered' => 'bg-primary',
                                        'paid' => 'bg-success',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($order->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Total Amount</th>
                            <td>₹{{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Paid Amount</th>
                            <td>₹{{ number_format($order->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Pending Amount</th>
                            <td>₹{{ number_format($order->pending_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Created By</th>
                            <td>{{ $order->creator->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Approved By</th>
                            <td>{{ $order->approver->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Order Date</th>
                            <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        @if ($order->delivery_date)
                            <tr>
                                <th>Delivery Date</th>
                                <td>{{ \Carbon\Carbon::parse($order->delivery_date)->format('d-m-Y') }}</td>
                            </tr>
                        @endif
                        @if ($order->notes)
                            <tr>
                                <th>Notes</th>
                                <td class="text-pre-wrap">{{ $order->notes }}</td>
                            </tr>
                        @endif
                    </table>

                    <div class="d-flex gap-2 flex-wrap">
                        @if (auth()->user()->role !== 'sales_admin')
                            <a href="{{ route('sales.orders.edit', $order) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-pen"></i> Edit Order
                            </a>
                        @endif
                        @if (
                            $order->status === 'pending' &&
                                auth()->user()->role !== 'sales_admin' &&
                                optional($order->creator)->role !== 'admin')
                            <form action="{{ url('/sales/orders/' . $order->id . '/approve') }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-success btn-sm"
                                    onclick="return confirm('Approve this order?')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form action="{{ url('/sales/orders/' . $order->id . '/reject') }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Reject this order?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        @endif
                        @if ($order->status === 'approved')
                            <form action="{{ url('/sales/orders/' . $order->id . '/deliver') }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-primary btn-sm"
                                    onclick="return confirm('Mark as delivered?')">
                                    <i class="fas fa-truck"></i> Mark Delivered
                                </button>
                            </form>
                        @endif
                        @if (auth()->user()->role !== 'sales_admin' && in_array($order->status, ['approved', 'delivered']))
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#driverModal">
                                <i class="fas fa-user-tie"></i>
                                {{ $order->driver_name ? 'Edit Driver' : 'Assign Driver' }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Driver Info Card --}}
            @if ($order->driver_name)
                <div class="card mt-3">
                    <div class="card-header"><i class="fas fa-user-tie me-1"></i> Assigned Driver</div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th>Driver</th>
                                <td>{{ $order->driver_name }}</td>
                            </tr>
                            <tr>
                                <th>WhatsApp</th>
                                <td>{{ $order->driver_whatsapp }}</td>
                            </tr>
                            @if ($order->driver_vehicle)
                                <tr>
                                    <th>Vehicle</th>
                                    <td>{{ $order->driver_vehicle }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Delivery Date</th>
                                <td>{{ \Carbon\Carbon::parse($order->delivery_date)->format('d-m-Y') }}</td>
                            </tr>
                        </table>
                        <a href="https://wa.me/91{{ $order->driver_whatsapp }}?text={{ urlencode('Delivery Order #' . $order->id . ' for ' . $order->customer->name . ' on ' . \Carbon\Carbon::parse($order->delivery_date)->format('d-m-Y') . '. Total: ₹' . number_format($order->total_amount, 2)) }}"
                            target="_blank" class="btn btn-success btn-sm mt-2">
                            <i class="fab fa-whatsapp"></i> Send WhatsApp to Driver
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-6">
            @if (in_array($order->status, ['approved', 'delivered']) && $order->pending_amount > 0)
                <div class="card mb-3">
                    <div class="card-header"><i class="fas fa-money-bill-wave me-1"></i> Record Payment</div>
                    <div class="card-body">
                        <form method="POST" action="{{ url('/sales/orders/' . $order->id . '/payments') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Amount (Max:
                                    ₹{{ number_format($order->pending_amount, 2) }})</label>
                                <input type="number" class="form-control" name="amount" step="0.01" min="0.01"
                                    max="{{ $order->pending_amount }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="upi">UPI</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">Record Payment</button>
                        </form>
                    </div>
                </div>
            @endif

            @if (auth()->user()->role !== 'sales_admin')
                <div class="card">
                    <div class="card-header"><i class="fas fa-sticky-note me-1"></i> Admin Notes</div>
                    <div class="card-body">
                        <form method="POST" action="{{ url('/sales/orders/' . $order->id . '/notes') }}">
                            @csrf @method('PATCH')
                            <div class="mb-2">
                                <textarea class="form-control" name="notes" rows="4"
                                    placeholder="Add internal notes, partial delivery info, special instructions…">{{ $order->notes }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <i class="fas fa-save"></i> Save Notes
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Order Items --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-box me-1"></i> Order Items</span>
            @if (auth()->user()->role !== 'sales_admin')
                <a href="{{ route('sales.orders.edit', $order) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-plus-circle me-1"></i> Edit / Add Items
                </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            @if (auth()->user()->role !== 'sales_admin')
                                <th>vs. List Price</th>
                            @endif
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            @php
                                $listPrice = $productCosts[$item->product_id] ?? null;
                                $diff = $listPrice ? $item->price - $listPrice : null;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->product->name }} ({{ $item->product->sku }})</td>
                                <td>{{ $item->quantity }} {{ $item->product->unit }}</td>
                                <td>₹{{ number_format($item->price, 2) }}</td>
                                @if (auth()->user()->role !== 'sales_admin')
                                    <td>
                                        @if ($diff === null)
                                            <span class="text-muted">No list price</span>
                                        @elseif ($diff > 0)
                                            <span class="fw-bold text-success">
                                                ₹{{ number_format($item->price, 2) }}
                                                <small>(+{{ number_format($diff, 2) }})</small>
                                            </span>
                                        @elseif ($diff < 0)
                                            <span class="fw-bold text-danger">
                                                ₹{{ number_format($item->price, 2) }}
                                                <small>({{ number_format($diff, 2) }})</small>
                                            </span>
                                        @else
                                            <span class="fw-bold text-primary">
                                                ₹{{ number_format($item->price, 2) }}
                                                <small>(same as list)</small>
                                            </span>
                                        @endif
                                    </td>
                                @endif
                                <td>₹{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="{{ auth()->user()->role !== 'sales_admin' ? 5 : 4 }}" class="text-end fw-bold">
                                Grand Total:</td>
                            <td class="fw-bold">₹{{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @if ($order->payments->count() > 0)
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-receipt me-1"></i> Payment History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->payments as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                                    <td>₹{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Assign Driver Modal (admin only) --}}
    @if (auth()->user()->role !== 'sales_admin')
        <div class="modal fade" id="driverModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ url('/sales/orders/' . $order->id . '/driver') }}">
                        @csrf @method('PATCH')
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-user-tie me-1"></i> Assign Driver</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Driver Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="driver_name"
                                    value="{{ $order->driver_name }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">WhatsApp Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">+91</span>
                                    <input type="text" class="form-control" name="driver_whatsapp"
                                        value="{{ $order->driver_whatsapp }}" placeholder="10-digit number"
                                        maxlength="15" pattern="[0-9]{10,15}" required>
                                </div>
                                <small class="text-muted">Used to send delivery details via WhatsApp</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Vehicle Number <span
                                        class="text-muted">(optional)</span></label>
                                <input type="text" class="form-control" name="driver_vehicle"
                                    value="{{ $order->driver_vehicle }}" placeholder="e.g. PB10AB1234">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="delivery_date"
                                    value="{{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '' }}"
                                    min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i>
                                After saving, use the <strong>Send WhatsApp to Driver</strong> button to manually send the
                                delivery message via WhatsApp Web/App.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Save Driver
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
