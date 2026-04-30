<?php

namespace App\Services;

use App\Events\TriggerNotificationEvent;
use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderLifecycleService
{
    private const TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['processing', 'cancelled'],
        'processing' => ['dispatched', 'cancelled'],
        'dispatched' => ['delivered'],
        'delivered' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function canTransition(?string $from, string $to): bool
    {
        $from = mb_strtolower((string) ($from ?: 'pending'));
        $to = mb_strtolower($to);

        if ($from === $to) {
            return true;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function transition(Order $order, string $to, ?int $changedBy = null, ?string $note = null): void
    {
        $from = mb_strtolower((string) $order->order_status);
        $to = mb_strtolower($to);

        if (! $this->canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'order_status' => 'Invalid order status transition from '.ucfirst($from).' to '.ucfirst($to).'.',
            ]);
        }

        if ($from === $to) {
            return;
        }

        $updates = [
            'order_status' => $to,
            'updated_by' => $changedBy,
        ];

        if (array_key_exists('status', $order->getAttributes())) {
            $updates['status'] = $to;
        }

        $order->update($updates);
        $this->log($order, $from, $to, $changedBy, $note);
        $this->dispatchVendorStatusNotification($order->fresh(['vendor']), $to);

        if ($to === 'delivered') {
            $this->dispatchDeliveredNotification($order->fresh(['customer']));
        }
    }

    public function log(Order $order, ?string $from, string $to, ?int $changedBy = null, ?string $note = null): void
    {
        OrderLog::create([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);
    }

    private function dispatchDeliveredNotification(Order $order): void
    {
        try {
            $customer = $order->customer;

            if (! $customer) {
                return;
            }

            event(new TriggerNotificationEvent('ORDER_DELIVERED', [
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
            Log::error('Order delivered template notification dispatch failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function dispatchVendorStatusNotification(Order $order, string $status): void
    {
        try {
            $vendor = $order->vendor;

            if (! $vendor) {
                return;
            }

            event(new TriggerNotificationEvent('ORDER_STATUS_UPDATE', [
                'recipient_type' => 'vendor',
                'recipient_id' => $vendor->id,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'name' => $vendor->vendor_name,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => ucfirst($status),
                'order_status' => ucfirst($status),
                'amount' => number_format((float) $order->total_amount, 2),
                'total_amount' => number_format((float) $order->total_amount, 2),
            ]));
        } catch (Throwable $exception) {
            Log::error('Order status vendor notification dispatch failed.', [
                'order_id' => $order->id,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
