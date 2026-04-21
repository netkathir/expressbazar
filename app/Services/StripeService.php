<?php

namespace App\Services;

use Illuminate\Support\Str;

class StripeService
{
    public function createPaymentIntent(int $amount, string $currency = 'inr', array $metadata = []): array
    {
        return [
            'id' => 'pi_' . Str::random(24),
            'client_secret' => 'pi_' . Str::random(24) . '_secret_' . Str::random(24),
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'requires_payment_method',
            'metadata' => $metadata,
        ];
    }

    public function confirmPayment(string $paymentIntentId, array $payload = []): array
    {
        return [
            'payment_intent_id' => $paymentIntentId,
            'status' => 'succeeded',
            'transaction_id' => 'txn_' . Str::random(18),
            'payload' => $payload,
        ];
    }
}
