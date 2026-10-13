<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $orders = Order::withCount('items')
            ->where(['created_by' => $user->id])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('order.index', compact('orders'));
    }

    public function view(Order $order)
    {
        /** @var \App\Models\User $user */
        $user = \request()->user();
        if ($order->created_by !== $user->id) {
            return response("You don't have permission to view this order", 403);
        }

        return view('order.view', compact('order'));
    }

    public function pay(Order $order)
    {
        /** @var \App\Models\User $user */
        $user = \request()->user();
        if ($order->created_by !== $user->id) {
            return response("You don't have permission to view this order", 403);
        }

        $payment = $order->payment;
        if (!$payment || $order->isPaid()) {
            return redirect()->route('order.view', $order)
                ->with('status', 'This order is already paid. Thank you!');
        }

        $paymentMethod = PaymentMethod::where('code', $payment->type)->first();

        return view('order.pay', compact('order', 'payment', 'paymentMethod'));
    }

    public function submitPayment(Request $request, Order $order)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        if ($order->created_by !== $user->id) {
            return response("You don't have permission to view this order", 403);
        }

        $payment = $order->payment;
        if (!$payment || $order->isPaid()) {
            return redirect()->route('order.view', $order);
        }

        $request->validate([
            'payment_proof' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:8192'],
        ]);

        if ($payment->payment_proof) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($payment->payment_proof);
        }

        $payment->payment_proof = $request->file('payment_proof')->store('payment-proofs', 'public');
        $payment->save();

        return redirect()->route('order.view', $order)
            ->with('status', 'Thank you! We received your payment proof and will verify it shortly.');
    }
}
