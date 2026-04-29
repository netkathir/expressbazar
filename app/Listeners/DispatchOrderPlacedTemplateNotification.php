<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\TriggerNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchOrderPlacedTemplateNotification implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        try {
            $order = $event->order->loadMissing(['customer', 'vendor']);
            $customer = $order->customer;

            if (! $customer) {
                return;
            }

            event(new TriggerNotificationEvent('ORDER_PLACED', [
                'recipient_type' => 'customer',
                'recipient_id' => $customer->id,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'name' => $customer->name,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => number_format((float) $order->total_amount, 2),
                'vendor_name' => $order->vendor?->vendor_name,
            ]));
        } catch (Throwable $exception) {
            Log::error('Order placed template notification dispatch failed.', [
                'order_id' => $event->order->id ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
