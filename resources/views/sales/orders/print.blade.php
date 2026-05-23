<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Order #{{ $order->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 14px; color: #222; margin: 24px; }
        h1 { font-size: 22px; margin: 0 0 8px; }
        .meta { margin-bottom: 20px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta th { text-align: left; width: 140px; padding: 4px 8px 4px 0; color: #555; font-weight: normal; }
        .meta td { padding: 4px 0; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.items th, table.items td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        table.items th { background: #f5f5f5; }
        table.items .num { text-align: right; }
        .totals { margin-top: 16px; width: 320px; margin-left: auto; }
        .totals table { width: 100%; }
        .totals td { padding: 6px 8px; }
        .totals .label { text-align: right; color: #555; }
        .totals .value { text-align: right; font-weight: 600; }
        .totals .grand td { font-size: 16px; border-top: 2px solid #333; padding-top: 10px; }
        .remark { margin-top: 20px; padding: 12px; background: #f9f9f9; border: 1px solid #ddd; }
        .no-print { margin-bottom: 16px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 12px; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" style="padding:8px 16px;cursor:pointer;">Print</button>
        <a href="{{ url('/sales/orders/' . $order->id) }}" style="margin-left:8px;">Back to order</a>
    </div>

    <h1>Sales Order #{{ $order->id }}</h1>
    <p style="margin:0;color:#666;">{{ $order->company->name ?? 'KEMTEX' }} &mdash; {{ $order->created_at->format('d M Y') }}</p>

    <div class="meta">
        <table>
            <tr>
                <th>Customer</th>
                <td><strong>{{ $order->customer->name }}</strong></td>
            </tr>
            @if ($order->customer->gst_number)
                <tr>
                    <th>GST No.</th>
                    <td>{{ $order->customer->gst_number }}</td>
                </tr>
            @endif
            @if ($order->customer->address)
                <tr>
                    <th>Address</th>
                    <td>{{ $order->customer->address }}</td>
                </tr>
            @endif
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($order->status) }}</td>
            </tr>
            <tr>
                <th>Prepared by</th>
                <td>{{ $order->creator->name ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="num">Qty</th>
                <th class="num">Rate (₹)</th>
                <th class="num">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product->name }} @if($item->product->sku)<small>({{ $item->product->sku }})</small>@endif</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format($item->price, 2) }}</td>
                    <td class="num">{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $subtotal = (float) ($order->subtotal > 0 ? $order->subtotal : $order->items->sum('total'));
        $gstRate = $order->gst_rate === null ? 18 : (int) $order->gst_rate;
        $gstAmount = $gstRate === 0
            ? 0.0
            : (float) ($order->gst_amount > 0 ? $order->gst_amount : round($subtotal * $gstRate / 100, 2));
        $discount = (float) ($order->discount_amount ?? 0);
        $grandTotal = (float) $order->total_amount;
    @endphp

    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">₹{{ number_format($subtotal, 2) }}</td>
            </tr>
            @if ($gstRate > 0)
                <tr>
                    <td class="label">GST ({{ $gstRate }}%)</td>
                    <td class="value">₹{{ number_format($gstAmount, 2) }}</td>
                </tr>
            @endif
            @if ($discount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value">− ₹{{ number_format($discount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand">
                <td class="label"><strong>Grand Total</strong></td>
                <td class="value"><strong>₹{{ number_format($grandTotal, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    @if ($order->notes)
        <div class="remark">
            <strong>Remark:</strong><br>
            {{ $order->notes }}
        </div>
    @endif

    <script>
        if (new URLSearchParams(window.location.search).get('auto') === '1') {
            window.onload = () => window.print();
        }
    </script>
</body>
</html>
