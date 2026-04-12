<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
        }

        .header {
            background: #2563eb;
            color: #fff;
            padding: 24px 32px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .body {
            padding: 28px 32px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .table th,
        .table td {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            text-align: left;
        }

        .table th {
            background: #f3f4f6;
        }

        .btn {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 28px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
        }

        .footer {
            padding: 16px 32px;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>New Sales Order — Pending Approval</h1>
        </div>
        <div class="body">
            <p>A new sales order has been placed by <strong>{{ $order->creator->name ?? 'a sales admin' }}</strong> and
                requires your approval.</p>

            <table class="table">
                <tr>
                    <th>Order #</th>
                    <td>{{ $order->id }}</td>
                </tr>
                <tr>
                    <th>Customer</th>
                    <td>{{ $order->customer->name }}</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td>₹{{ number_format($order->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Created By</th>
                    <td>{{ $order->creator->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                </tr>
            </table>

            <h3 style="margin-top:20px;">Order Items</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₹{{ number_format($item->price, 2) }}</td>
                            <td>₹{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <a href="{{ url('/sales/orders/' . $order->id) }}" class="btn">Review &amp; Approve Order</a>
        </div>
        <div class="footer">
            Kemtex Management System &mdash; This is an automated notification.
        </div>
    </div>
</body>

</html>
