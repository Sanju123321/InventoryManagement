<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, SalesOrder $order)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (! in_array($order->status, ['approved', 'dispatched', 'paid'])) {
            return back()->withErrors(['status' => 'Payments can only be made on approved or dispatched orders.']);
        }

        $order->refresh();
        $maxAmount = max(0, round((float) $order->total_amount - (float) $order->paid_amount, 2));

        if ($maxAmount <= 0) {
            return back()->withErrors(['amount' => 'This order has no pending balance.']);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|lte:' . $maxAmount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,upi,cheque,other',
        ], [
            'amount.lte' => 'Payment amount cannot exceed the pending balance of ₹' . number_format($maxAmount, 2) . '.',
        ]);

        $amount = round((float) $request->amount, 2);

        Payment::create([
            'company_id' => auth()->user()->company_id,
            'sales_order_id' => $order->id,
            'amount' => $amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
        ]);

        $newPaid = round((float) $order->paid_amount + $amount, 2);
        $newPending = max(0, round((float) $order->total_amount - $newPaid, 2));

        $order->update([
            'paid_amount' => $newPaid,
            'pending_amount' => $newPending,
        ]);

        return back()->with('success', 'Payment of ₹' . number_format($amount, 2) . ' recorded successfully.');
    }
}
