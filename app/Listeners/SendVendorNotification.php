<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\NotificationLog;
use App\Notifications\VendorOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendVendorNotification implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        try {
            $order = $event->order->loadMissing(['vendor', 'customer', 'items']);
            $vendor = $order->vendor;

            if (! $vendor) {
                return;
            }

            $this->logInAppNotification($order);
            $this->sendDatabaseNotification($vendor, $order);
        } catch (Throwable $exception) {
            Log::error('Vendor notification listener failed.', [
                'order_id' => $event->order->id ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function logInAppNotification($order): void
    {
        if (! Schema::hasTable('notification_logs')) {
            return;
        }

        try {
            NotificationLog::create([
                'recipient_type' => 'vendor',
                'recipient_id' => $order->vendor_id,
                'channel' => 'in_app',
                'message' => json_encode([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'message' => 'New order received. Order ID: #'.$order->order_number,
                    'total' => (float) $order->total_amount,
                ]),
                'status' => 'sent',
            ]);
        } catch (Throwable $exception) {
            Log::error('Vendor in-app notification failed.', [
                'order_id' => $order->id,
                'vendor_id' => $order->vendor_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendDatabaseNotification($vendor, $order): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        try {
            Notification::send($vendor, new VendorOrderNotification($order));
        } catch (Throwable $exception) {
            Log::error('Vendor database notification failed.', [
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
