@php
    $badgeClass = match ($order->status) {
        'pending' => 'bg-warning text-dark',
        'approved' => 'bg-info',
        'rejected' => 'bg-danger',
        'dispatched' => 'bg-primary',
        'paid' => 'bg-success',
        default => 'bg-secondary',
    };
@endphp

@if ($layout === 'table')
    <tr>
        <td><span class="badge bg-dark rounded-pill">#{{ $order->id }}</span></td>
        <td>{{ $order->firm->name ?? '-' }}</td>
        <td class="fw-semibold">{{ $order->customer->name }}</td>
        <td class="fw-bold">₹{{ number_format($order->total_amount, 2) }}</td>
        <td class="text-success">₹{{ number_format($order->paid_amount, 2) }}</td>
        <td class="text-danger">₹{{ number_format($order->pending_amount, 2) }}</td>
        <td><span class="badge {{ $badgeClass }} rounded-pill">{{ $order->statusLabel() }}</span></td>
        <td>
            @if ($order->receiving_ok)
                <span class="badge bg-success rounded-pill">Receiving Ok</span>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>
        <td><small>{{ $order->creator->name ?? '-' }}</small></td>
        <td><small>{{ $order->created_at->format('d-m-Y') }}</small></td>
        <td>
            <div class="action-group">
                <a href="{{ url('/sales/orders/' . $order->id) }}" class="btn btn-sm btn-outline-info" title="View order">
                    <i class="fas fa-eye"></i>
                </a>
                @if (auth()->user()->isAdmin())
                    <form action="{{ route('sales.orders.destroy', $order) }}" method="POST"
                        onsubmit="return confirm('Delete order #{{ $order->id }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete order">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@elseif ($layout === 'compact')
    <tr>
        <td><span class="badge bg-dark rounded-pill">#{{ $order->id }}</span></td>
        <td>{{ $order->firm->name ?? '-' }}</td>
        <td class="fw-semibold">{{ $order->customer->name }}</td>
        <td class="fw-bold">₹{{ number_format($order->total_amount, 2) }}</td>
        <td><span class="badge {{ $badgeClass }} rounded-pill">{{ $order->statusLabel() }}</span></td>
        <td>
            @if ($order->receiving_ok)
                <span class="badge bg-success rounded-pill">Receiving Ok</span>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>
        <td>
            <div class="action-group">
                <a href="{{ url('/sales/orders/' . $order->id) }}" class="btn btn-sm btn-outline-info" title="View order">
                    <i class="fas fa-eye"></i>
                </a>
                @if (auth()->user()->isAdmin())
                    <form action="{{ route('sales.orders.destroy', $order) }}" method="POST"
                        onsubmit="return confirm('Delete order #{{ $order->id }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete order">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@else
    <div class="order-card shadow-sm">
        <div class="order-card-header">
            <div>
                <span class="badge bg-dark rounded-pill">#{{ $order->id }}</span>
                <div class="fw-semibold mt-1">{{ $order->customer->name }}</div>
                <small class="text-muted">{{ $order->firm->name ?? 'No firm' }} · {{ $order->created_at->format('d-m-Y') }}</small>
            </div>
            <div class="text-end">
                <span class="badge {{ $badgeClass }} rounded-pill">{{ $order->statusLabel() }}</span>
                @if ($order->receiving_ok)
                    <div class="mt-1"><span class="badge bg-success rounded-pill">Receiving Ok</span></div>
                @endif
            </div>
        </div>
        <div class="order-card-body">
            <div class="order-meta-grid">
                <div class="order-meta-item">
                    <label>Grand Total</label>
                    <span>₹{{ number_format($order->total_amount, 2) }}</span>
                </div>
                <div class="order-meta-item">
                    <label>Paid</label>
                    <span class="text-success">₹{{ number_format($order->paid_amount, 2) }}</span>
                </div>
                <div class="order-meta-item">
                    <label>Pending</label>
                    <span class="text-danger">₹{{ number_format($order->pending_amount, 2) }}</span>
                </div>
                <div class="order-meta-item">
                    <label>Created By</label>
                    <span>{{ $order->creator->name ?? '-' }}</span>
                </div>
            </div>
        </div>
        <div class="order-card-actions">
            <a href="{{ url('/sales/orders/' . $order->id) }}" class="btn btn-sm btn-outline-info">
                <i class="fas fa-eye me-1"></i> View
            </a>
            @if (auth()->user()->isAdmin())
                <form action="{{ route('sales.orders.destroy', $order) }}" method="POST"
                    onsubmit="return confirm('Delete order #{{ $order->id }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash-can me-1"></i> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif
