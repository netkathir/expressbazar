<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\TriggerNotificationEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchOrderPlacedTemplateNotification
{
    public function handle(OrderPlaced $event): void
    {
        try {
            $order = $event->order->loadMissing(['customer', 'items']);
            $customer = $order->customer;

            if ($customer) {
                event(new TriggerNotificationEvent('ORDER_CONFIRMED', [
                    'recipient_type' => 'customer',
                    'recipient_id' => $customer->id,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'name' => $customer->name,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => number_format((float) $order->total_amount, 2),
                    'total_amount' => number_format((float) $order->total_amount, 2),
                    'items' => $this->itemsSummary($order),
                ]));
            }

            $order->loadMissing('vendor');
            $vendor = $order->vendor;
            if ($vendor) {
                event(new TriggerNotificationEvent('ORDER_RECEIVED_VENDOR', [
                    'recipient_type' => 'vendor',
                    'recipient_id' => $vendor->id,
                    'email' => $vendor->email,
                    'phone' => $vendor->phone,
                    'name' => $vendor->vendor_name,
                    'customer_name' => $customer?->name,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => number_format((float) $order->total_amount, 2),
                    'total_amount' => number_format((float) $order->total_amount, 2),
                    'items' => $this->itemsSummary($order),
                ]));
            }
        } catch (Throwable $exception) {
            Log::error('Order placed template notification dispatch failed.', [
                'order_id' => $event->order->id ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function itemsSummary($order): string
    {
        $order->loadMissing('items');

        return $order->items
            ->map(fn ($item) => $item->item_name.' x '.$item->quantity)
            ->implode(', ');
    }
}
