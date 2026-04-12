<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, SalesOrder $order)
    {
        abort_unless($order->company_id === auth()->user()->company_id, 403);

        if (!in_array($order->status, ['approved', 'delivered'])) {
            return back()->withErrors(['status' => 'Payments can only be made on approved or delivered orders.']);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $order->pending_amount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,upi,cheque,other',
        ]);

        Payment::create([
            'company_id' => auth()->user()->company_id,
            'sales_order_id' => $order->id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
        ]);

        $newPaid = $order->paid_amount + $request->amount;
        $newPending = $order->total_amount - $newPaid;

        $order->update([
            'paid_amount' => $newPaid,
            'pending_amount' => $newPending,
            'status' => $newPending <= 0 ? 'paid' : $order->status,
        ]);

        return back()->with('success', 'Payment of ₹' . number_format($request->amount, 2) . ' recorded successfully.');
    }
}
