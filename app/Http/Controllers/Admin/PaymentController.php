<?php

namespace App\Http\Controllers\Admin;

use App\Events\TriggerNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::query()
            ->with(['order.customer', 'order.vendor'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('transaction_id', 'like', "%{$search}%");
            })
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.index', [
            'title' => 'Payment Management',
            'activeMenu' => 'payments',
            'payments' => $payments,
        ]);
    }

    public function create()
    {
        return view('admin.payments.form', [
            'title' => 'Add Payment',
            'activeMenu' => 'payments',
            'payment' => new Payment(),
            'orders' => Order::orderByDesc('id')->limit(50)->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayment($request);
        $payment = Payment::create($data);

        if ($payment->status === 'paid') {
            $this->dispatchPaymentSuccessNotification($payment->load('order.customer'));
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment created successfully.');
    }

    public function edit(Payment $payment)
    {
        return view('admin.payments.form', [
            'title' => 'Edit Payment',
            'activeMenu' => 'payments',
            'payment' => $payment,
            'orders' => Order::orderByDesc('id')->limit(50)->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $this->validatePayment($request, $payment);
        $wasPaid = $payment->status === 'paid';
        $payment->update($data);

        if (! $wasPaid && $payment->status === 'paid') {
            $this->dispatchPaymentSuccessNotification($payment->load('order.customer'));
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()->route('admin.payments.index')->with('success', 'Payment deleted successfully.');
    }

    private function validatePayment(Request $request, ?Payment $payment = null): array
    {
        return $request->validate([
            'order_id' => ['nullable', 'exists:orders,id'],
            'transaction_id' => ['required', 'string', 'max:255', Rule::unique('payments', 'transaction_id')->ignore($payment?->id)],
            'payment_method' => ['required', Rule::in(['stripe', 'cod', 'bank_transfer', 'wallet'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'gateway_response' => ['nullable', 'string'],
            'paid_at' => ['nullable', 'date'],
        ]);
    }

    private function dispatchPaymentSuccessNotification(Payment $payment): void
    {
        try {
            $order = $payment->order;
            $customer = $order?->customer;

            if (! $order || ! $customer) {
                return;
            }

            event(new TriggerNotificationEvent('PAYMENT_SUCCESS', [
                'recipient_type' => 'customer',
                'recipient_id' => $customer->id,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'name' => $customer->name,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => number_format((float) $payment->amount, 2),
                'total_amount' => number_format((float) $order->total_amount, 2),
                'transaction_id' => $payment->transaction_id,
            ]));
        } catch (Throwable $exception) {
            Log::error('Admin payment success template notification dispatch failed.', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
