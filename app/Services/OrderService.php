<?php

namespace App\Services;

class OrderService
{
    public function createFromCart(array $pricing, array $checkoutData): array
    {
        return [
            'order_number' => 'ORD-' . now()->format('YmdHis'),
            'status' => 'pending',
            'payment_status' => $checkoutData['payment_status'] ?? 'pending',
            'shipping_name' => $checkoutData['shipping_name'] ?? 'Customer',
            'shipping_phone' => $checkoutData['shipping_phone'] ?? '',
            'shipping_address' => $checkoutData['shipping_address'] ?? '',
            'currency' => 'INR',
            'subtotal' => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'delivery_fee' => $checkoutData['delivery_fee'] ?? 0,
            'grand_total' => $pricing['grand_total'] + ($checkoutData['delivery_fee'] ?? 0),
            'items' => $pricing['items'],
        ];
    }
}
