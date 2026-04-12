@extends('layouts.app')

@section('title', 'Ledger — ' . $customer->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <div>
            <h1 class="mb-0">Customer Ledger</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/customers') }}">Customers</a></li>
                <li class="breadcrumb-item active">{{ $customer->name }}</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                <i class="fas fa-plus me-1"></i> Add Payment
            </button>
            <a href="{{ route('customers.ledger.export', $customer) }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-csv me-1"></i> Export Ledger
            </a>
            <a href="{{ url('/customers') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4 mt-1">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Total Billed</div>
                <div class="fs-5 fw-bold text-primary">₹{{ number_format($totalPurchase, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Total Received</div>
                <div class="fs-5 fw-bold text-success">₹{{ number_format($totalPaid, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Outstanding</div>
                <div class="fs-5 fw-bold {{ $totalPending > 0 ? 'text-danger' : 'text-success' }}">
                    ₹{{ number_format($totalPending, 2) }}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small">Total Orders</div>
                <div class="fs-5 fw-bold text-secondary">{{ $orders->count() }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Customer Info --}}
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="fas fa-user me-1"></i> Customer Info</div>
                <div class="card-body small">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted" style="width:90px">Name</th><td>{{ $customer->name }}</td></tr>
                        <tr><th class="text-muted">Phone</th><td>{{ $customer->phone ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Contact</th><td>{{ $customer->contact_details ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Email</th><td>{{ $customer->email ?? '-' }}</td></tr>
                        <tr><th class="text-muted">GST</th><td>{{ $customer->gst_number ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Address</th><td>{{ $customer->address ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Orders Summary --}}
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="fas fa-file-invoice me-1"></i> Orders Summary</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th class="text-end">Billed</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Pending</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders->sortByDesc('created_at') as $order)
                                    <tr>
                                        <td>#{{ $order->id }}</td>
                                        <td>{{ $order->created_at->format('d-m-Y') }}</td>
                                        <td class="text-end">₹{{ number_format($order->total_amount, 2) }}</td>
                                        <td class="text-end text-success">₹{{ number_format($order->paid_amount, 2) }}</td>
                                        <td class="text-end {{ $order->pending_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                            ₹{{ number_format($order->pending_amount, 2) }}
                                        </td>
                                        <td>
                                            @php
                                                $bc = match($order->status) {
                                                    'pending'   => 'bg-warning text-dark',
                                                    'approved'  => 'bg-info',
                                                    'rejected'  => 'bg-danger',
                                                    'delivered' => 'bg-primary',
                                                    'paid'      => 'bg-success',
                                                    default     => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $bc }}">{{ ucfirst($order->status) }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ url('/sales/orders/' . $order->id) }}"
                                               class="btn btn-outline-info btn-sm py-0 px-1">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">No orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- LEDGER TABLE --}}
    <div class="card shadow-sm mb-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-book me-1"></i> Transaction Ledger</span>
            <span class="badge {{ $totalPending > 0 ? 'bg-danger' : 'bg-success' }} fs-6">
                Balance Due: ₹{{ number_format($totalPending, 2) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:110px">Date</th>
                            <th>Description</th>
                            <th>Method</th>
                            <th>Ref / Note</th>
                            <th class="text-end fw-bold text-white" style="width:130px">Order Amount</th>
                            <th class="text-end fw-bold text-white" style="width:130px">Credited</th>
                            <th class="text-end" style="width:140px">Balance Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledger as $entry)
                            <tr class="{{ $entry['type'] === 'order' ? 'table-light' : '' }}">
                                <td class="text-nowrap">
                                    {{ \Carbon\Carbon::parse($entry['date'])->format('d-m-Y') }}
                                </td>
                                <td>
                                    @if($entry['link'])
                                        <a href="{{ $entry['link'] }}" class="text-decoration-none">
                                            {{ $entry['description'] }}
                                        </a>
                                    @else
                                        {{ $entry['description'] }}
                                    @endif
                                    @if($entry['type'] === 'customer_payment')
                                        <span class="badge bg-info text-dark ms-1" style="font-size:0.7em">Lump Sum</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $entry['method'] ? ucfirst(str_replace('_', ' ', $entry['method'])) : '—' }}
                                </td>
                                <td class="text-muted small">{{ $entry['ref'] ?? '—' }}</td>
                                <td class="text-end fw-semibold">
                                    @if($entry['debit'] > 0)
                                        <span class="text-danger">₹{{ number_format($entry['debit'], 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">
                                    @if($entry['credit'] > 0)
                                        <span class="text-success">₹{{ number_format($entry['credit'], 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $entry['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                    ₹{{ number_format($entry['balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($ledger->count() > 0)
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">Totals</td>
                                <td class="text-end text-danger">₹{{ number_format($totalPurchase, 2) }}</td>
                                <td class="text-end text-success">₹{{ number_format($totalPaid, 2) }}</td>
                                <td class="text-end {{ $totalPending > 0 ? 'text-danger' : 'text-success' }}">
                                    ₹{{ number_format($totalPending, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Add Payment Modal --}}
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('customers.payments.store', $customer) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-money-bill-wave me-1"></i> Record Payment — {{ $customer->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small py-2 mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Payment will auto-allocate to the <strong>oldest pending orders first</strong> (FIFO).
                            Outstanding balance: <strong class="{{ $totalPending > 0 ? 'text-danger' : '' }}">
                                ₹{{ number_format($totalPending, 2) }}
                            </strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" min="0.01" step="0.01"
                                   placeholder="e.g. 200000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reference No.
                                <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="reference" class="form-control"
                                   placeholder="Cheque no., UTR, UPI Ref…">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes
                                <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="notes" class="form-control"
                                   placeholder="e.g. Advance, Partial payment…">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Save Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
