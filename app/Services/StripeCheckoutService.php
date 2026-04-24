<?php

namespace App\Services;

use App\Models\Order;
use Stripe\StripeClient;
use RuntimeException;

class StripeCheckoutService
{
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            throw new RuntimeException('Stripe is not configured.');
        }

        $order->loadMissing('items');

        $stripe = new StripeClient($secret);

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'payment_method_types' => ['card'],
            'client_reference_id' => $order->order_number,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                ],
            ],
            'line_items' => $this->buildLineItems($order),
        ];

        if ($order->customer?->email) {
            $payload['customer_email'] = $order->customer->email;
        }

        return $stripe->checkout->sessions->create($payload)->toArray();
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            throw new RuntimeException('Stripe is not configured.');
        }

        $stripe = new StripeClient($secret);

        return $stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ])->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildLineItems(Order $order): array
    {
        $lineItems = [];

        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'inr',
                    'product_data' => [
                        'name' => $item->item_name,
                    ],
                    'unit_amount' => (int) round(((float) $item->price) * 100),
                ],
                'quantity' => (int) $item->quantity,
            ];
        }

        if ((float) $order->delivery_charge > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'inr',
                    'product_data' => [
                        'name' => 'Delivery Charge',
                    ],
                    'unit_amount' => (int) round(((float) $order->delivery_charge) * 100),
                ],
                'quantity' => 1,
            ];
        }

        if ($lineItems === []) {
            throw new RuntimeException('Unable to create Stripe checkout session without order items.');
        }

        return $lineItems;
    }
}
