<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use App\Notifications\CustomerBellNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotificationHelper
{
    public static function sendNotification(
        int $userId,
        string $title,
        string $message,
        ?string $type = null,
        array $meta = []
    ): void {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        try {
            $user = User::query()
                ->whereKey($userId)
                ->where('role', 'customer')
                ->first();

            if (! $user || self::alreadySent($user, $title, $type, $meta)) {
                return;
            }

            $user->notify(new CustomerBellNotification($title, $message, $type, $meta));
        } catch (Throwable $exception) {
            Log::error('Customer bell notification failed.', [
                'user_id' => $userId,
                'title' => $title,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public static function sendOrderPlaced(Order $order): void
    {
        self::sendOrderNotification(
            $order,
            'Order Placed',
            'Your order #'.self::orderLabel($order).' has been placed successfully.',
            'order',
            'order_placed'
        );
    }

    public static function sendPaymentSuccessful(Order $order): void
    {
        self::sendOrderNotification(
            $order,
            'Payment Successful',
            'Payment completed successfully for Order #'.self::orderLabel($order).'.',
            'payment',
            'payment_success'
        );
    }

    public static function sendOrderStatus(Order $order, string $status): void
    {
        $status = mb_strtolower($status);

        $map = [
            'accepted' => ['Order Confirmed', 'has been confirmed.', 'order_confirmed'],
            'dispatched' => ['Order Dispatched', 'has been dispatched.', 'order_dispatched'],
            'delivered' => ['Order Delivered', 'has been delivered.', 'order_delivered'],
        ];

        if (! isset($map[$status])) {
            return;
        }

        [$title, $suffix, $eventKey] = $map[$status];

        self::sendOrderNotification(
            $order,
            $title,
            'Your order #'.self::orderLabel($order).' '.$suffix,
            'order',
            $eventKey,
            ['status' => $status]
        );
    }

    private static function sendOrderNotification(
        Order $order,
        string $title,
        string $message,
        string $type,
        string $eventKey,
        array $extra = []
    ): void {
        $userId = (int) ($order->customer_id ?: $order->user_id ?? 0);

        if ($userId < 1) {
            return;
        }

        self::sendNotification($userId, $title, $message, $type, array_merge([
            'event' => $eventKey,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ], $extra));
    }

    private static function alreadySent(User $user, string $title, ?string $type, array $meta): bool
    {
        return $user->notifications()
            ->where('type', CustomerBellNotification::class)
            ->latest()
            ->limit(50)
            ->get()
            ->contains(function ($notification) use ($title, $type, $meta) {
                $data = $notification->data ?? [];

                if (($data['title'] ?? null) !== $title) {
                    return false;
                }

                if ($type !== null && ($data['category'] ?? null) !== $type) {
                    return false;
                }

                foreach (['event', 'order_id', 'status'] as $key) {
                    if (array_key_exists($key, $meta) && (string) ($data[$key] ?? '') !== (string) $meta[$key]) {
                        return false;
                    }
                }

                return true;
            });
    }

    private static function orderLabel(Order $order): string
    {
        return $order->order_number ?: (string) $order->id;
    }
}
