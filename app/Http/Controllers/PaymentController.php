<?php

namespace App\Http\Controllers;

use App\Events\TriggerNotificationEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private StripeCheckoutService $stripeCheckoutService)
    {
    }

    public function checkout(Request $request, Order $order)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        $payment = $order->payments()->latest()->first();
        if (! $payment || $payment->payment_method !== 'online') {
            return redirect()
                ->route('storefront.orders.show', $order)
                ->withErrors(['payment' => 'Online payment is not available for this order.']);
        }

        $successUrl = route('storefront.orders.success', $order);
        $cancelUrl = route('storefront.orders.cancel', $order);

        try {
            $session = $this->stripeCheckoutService->createCheckoutSession($order, $successUrl, $cancelUrl);
        } catch (Throwable $exception) {
            Log::error('Stripe checkout session creation failed.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $user->id,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            $payment->update([
                'status' => 'failed',
                'gateway_response' => json_encode([
                    'source' => 'stripe_checkout',
                    'status' => 'failed',
                    'reason' => $exception->getMessage(),
                ]),
            ]);

            $order->update([
                'status' => 'failed',
                'payment_status' => 'failed',
                'updated_by' => $user->id,
            ]);

            return redirect()
                ->route('storefront.orders.show', $order)
                ->withErrors(['payment' => 'Stripe checkout could not be started. Please try again.']);
        }

        DB::transaction(function () use ($order, $payment, $session, $user) {
            $order->update([
                'status' => 'pending',
                'payment_status' => 'pending',
                'stripe_session_id' => $session['id'] ?? null,
                'stripe_payment_intent' => null,
                'updated_by' => $user->id,
            ]);

            $payment->update([
                'gateway_response' => json_encode([
                    'source' => 'stripe_checkout',
                    'stripe_session_id' => $session['id'] ?? null,
                    'stripe_checkout_url' => $session['url'] ?? null,
                    'status' => 'checkout_started',
                ]),
            ]);
        });

        return redirect()->away($session['url']);
    }

    public function webhook(Request $request)
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '') {
            return response()->json(['error' => 'Stripe webhook secret is not configured.'], 500);
        }

        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $exception) {
            Log::warning('Stripe webhook signature verification failed.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid Stripe signature.'], 400);
        } catch (Throwable $exception) {
            Log::warning('Stripe webhook payload was invalid.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid Stripe payload.'], 400);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'payment_intent.succeeded'], true)) {
            $dataObject = $event->data->object;
            $sessionId = $dataObject->id ?? null;
            $paymentIntentId = $dataObject->payment_intent ?? ($dataObject->id ?? null);
            $orderId = $dataObject->metadata->order_id ?? null;

            if ($event->type === 'payment_intent.succeeded') {
                $paymentIntentId = $dataObject->id ?? null;
                $orderId = $dataObject->metadata->order_id ?? null;
            }

            if ($orderId) {
                $paidOrder = $this->markOrderAsPaidByOrderId((int) $orderId, $sessionId, $paymentIntentId);
            } elseif ($sessionId) {
                $paidOrder = $this->markOrderAsPaid($sessionId, $paymentIntentId);
            } else {
                $paidOrder = null;
            }

            if ($paidOrder) {
                $this->dispatchPaymentSuccessNotification($paidOrder);
            }
        }

        if ($event->type === 'checkout.session.expired') {
            $sessionId = $event->data->object->id ?? null;
            if ($sessionId) {
                $this->markOrderAsFailed($sessionId, 'expired');
            }
        }

        if (in_array($event->type, ['payment_intent.payment_failed', 'checkout.session.async_payment_failed'], true)) {
            $dataObject = $event->data->object;
            $sessionId = $dataObject->id ?? ($dataObject->metadata->stripe_session_id ?? null);
            if ($sessionId) {
                $this->markOrderAsFailed($sessionId, 'failed');
            }
        }

        return response()->json(['received' => true]);
    }

    private function markOrderAsPaid(?string $sessionId, ?string $paymentIntentId): ?Order
    {
        if (! $sessionId) {
            return null;
        }

        return DB::transaction(function () use ($sessionId, $paymentIntentId) {
            $order = Order::query()
                ->where('stripe_session_id', $sessionId)
                ->first();

            if (! $order) {
                return null;
            }

            if ($order->payment_status === 'paid') {
                return null;
            }

            $payment = $order->payments()
                ->where('payment_method', 'online')
                ->latest()
                ->first();

            $order->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'stripe_payment_intent' => $paymentIntentId,
            ]);

            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => json_encode([
                        'source' => 'stripe_webhook',
                        'status' => 'paid',
                        'stripe_session_id' => $sessionId,
                        'stripe_payment_intent' => $paymentIntentId,
                    ]),
                ]);
            }

            return $order->fresh(['customer']);
        });
    }

    private function markOrderAsPaidByOrderId(int $orderId, ?string $sessionId, ?string $paymentIntentId): ?Order
    {
        return DB::transaction(function () use ($orderId, $sessionId, $paymentIntentId) {
            $order = Order::query()->find($orderId);

            if (! $order) {
                return null;
            }

            if ($order->payment_status === 'paid') {
                return null;
            }

            $payment = $order->payments()
                ->where('payment_method', 'online')
                ->latest()
                ->first();

            $order->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'stripe_session_id' => $sessionId ?: $order->stripe_session_id,
                'stripe_payment_intent' => $paymentIntentId,
            ]);

            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => json_encode([
                        'source' => 'stripe_webhook',
                        'status' => 'paid',
                        'stripe_session_id' => $sessionId ?: $order->stripe_session_id,
                        'stripe_payment_intent' => $paymentIntentId,
                    ]),
                ]);
            }

            return $order->fresh(['customer']);
        });
    }

    private function markOrderAsFailed(?string $sessionId, string $status): void
    {
        if (! $sessionId) {
            return;
        }

        DB::transaction(function () use ($sessionId, $status) {
            $order = Order::query()
                ->where('stripe_session_id', $sessionId)
                ->first();

            if (! $order) {
                return;
            }

            $order->update([
                'status' => $status === 'expired' ? 'failed' : $order->status,
                'payment_status' => 'failed',
            ]);

            $payment = $order->payments()
                ->where('payment_method', 'online')
                ->latest()
                ->first();

            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'gateway_response' => json_encode([
                        'source' => 'stripe_webhook',
                        'status' => $status,
                        'stripe_session_id' => $sessionId,
                    ]),
                ]);
            }
        });
    }

    private function dispatchPaymentSuccessNotification(Order $order): void
    {
        try {
            $customer = $order->customer;

            if (! $customer) {
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
                'amount' => number_format((float) $order->total_amount, 2),
                'total_amount' => number_format((float) $order->total_amount, 2),
            ]));
        } catch (Throwable $exception) {
            Log::error('Payment success template notification dispatch failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
