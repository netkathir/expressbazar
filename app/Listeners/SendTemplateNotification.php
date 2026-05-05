<?php

namespace App\Listeners;

use App\Events\TriggerNotificationEvent;
use App\Mail\GenericTemplateMail;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendTemplateNotification
{
    public function __construct(private NotificationTemplateService $templates)
    {
    }

    public function handle(TriggerNotificationEvent $event): void
    {
        $template = $this->templates->findActive($event->trigger, $event->data['channel'] ?? 'email');

        if (! $template) {
            $this->sendFallbackEmail($event);
            return;
        }

        $message = $this->templates->render($template->message_body, $event->data);
        $duplicateKey = $this->duplicateKey($event, $template);

        if ($this->wasAlreadySent($template, $event->data, $template->channel, $message)) {
            return;
        }

        if ($duplicateKey && ! Cache::add($duplicateKey, true, now()->addDay())) {
            return;
        }

        try {
            if ($template->channel === 'email') {
                if ($this->sendEmail($template, $message, $event->data)) {
                    $this->logNotification($template->id, $event->data, 'email', $message, 'sent');
                } else {
                    $this->releaseDuplicateKey($duplicateKey);
                }

                return;
            }

            if ($template->channel === 'sms') {
                if ($this->sendSms($message, $event->data)) {
                    $this->logNotification($template->id, $event->data, 'sms', $message, 'sent');
                } else {
                    $this->releaseDuplicateKey($duplicateKey);
                }
            }
        } catch (Throwable $exception) {
            $this->releaseDuplicateKey($duplicateKey);

            Log::error('Template notification failed.', [
                'trigger' => $event->trigger,
                'template_id' => $template->id,
                'channel' => $template->channel,
                'error' => $exception->getMessage(),
            ]);

            $this->logNotification($template->id, $event->data, $template->channel, $message, 'failed', $exception->getMessage());
        }
    }

    private function sendFallbackEmail(TriggerNotificationEvent $event): void
    {
        $email = $event->data['email'] ?? null;

        if (! $email) {
            return;
        }

        $fallback = $this->fallbackTemplate($event->trigger, $event->data);

        if (! $fallback) {
            return;
        }

        try {
            Mail::to($email)->send(new GenericTemplateMail($fallback['subject'], $fallback['message']));
            $this->logNotification(null, $event->data, 'email', $fallback['message'], 'sent');
        } catch (Throwable $exception) {
            Log::error('Fallback template notification failed.', [
                'trigger' => $event->trigger,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            $this->logNotification(null, $event->data, 'email', $fallback['message'], 'failed', $exception->getMessage());
        }
    }

    private function fallbackTemplate(string $trigger, array $data): ?array
    {
        $orderNumber = $data['order_number'] ?? $data['order_id'] ?? '';
        $amount = $data['amount'] ?? $data['total_amount'] ?? '';
        $name = $data['name'] ?? 'Customer';
        $status = $data['status'] ?? $data['order_status'] ?? '';
        $vendorName = $data['vendor_name'] ?? 'Express Bazar';

        return match ($trigger) {
            'ORDER_CONFIRMED' => [
                'subject' => "Your order #{$orderNumber} is confirmed",
                'message' => "Hello {$name},\n\nYour order #{$orderNumber} has been confirmed.\n\nTotal: {$amount}\n\nThank you for shopping with us.",
            ],
            'ORDER_RECEIVED_VENDOR' => [
                'subject' => "New order received #{$orderNumber}",
                'message' => "Hello {$name},\n\nA new order #{$orderNumber} has been received.\n\nCustomer: ".($data['customer_name'] ?? '-')."\nTotal: {$amount}",
            ],
            'ORDER_STATUS_UPDATE' => [
                'subject' => "Order #{$orderNumber} is now {$status}",
                'message' => "Hello {$name},\n\nYour order #{$orderNumber} status has been updated to {$status}.\n\nVendor: {$vendorName}\nTotal: {$amount}",
            ],
            'ORDER_DELIVERED' => [
                'subject' => "Your order #{$orderNumber} has been delivered",
                'message' => "Hello {$name},\n\nYour order #{$orderNumber} has been delivered successfully.\n\nThank you for shopping with us.",
            ],
            'PAYMENT_SUCCESS' => [
                'subject' => "Payment successful for order #{$orderNumber}",
                'message' => "Hello {$name},\n\nPayment was successful for order #{$orderNumber}.\n\nAmount: {$amount}",
            ],
            default => null,
        };
    }

    private function sendEmail(NotificationTemplate $template, string $message, array $data): bool
    {
        $email = $data['email'] ?? null;

        if (! $email) {
            return false;
        }

        $subject = $this->templates->render($template->subject ?: $template->template_name, $data);

        Mail::to($email)->send(new GenericTemplateMail($subject, $message));

        return true;
    }

    private function sendSms(string $message, array $data): bool
    {
        $phone = $data['phone'] ?? null;

        if (! $phone) {
            return false;
        }

        app(SmsService::class)->send($phone, $message);

        return true;
    }

    private function logNotification(?int $templateId, array $data, string $channel, string $message, string $status, ?string $error = null): void
    {
        if (! Schema::hasTable('notification_logs')) {
            return;
        }

        NotificationLog::create([
            'template_id' => $templateId,
            'recipient_type' => $data['recipient_type'] ?? 'user',
            'recipient_id' => $data['recipient_id'] ?? null,
            'channel' => $channel,
            'message' => $message,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    private function duplicateKey(TriggerNotificationEvent $event, NotificationTemplate $template): ?string
    {
        $orderId = $event->data['order_id'] ?? $event->data['order_number'] ?? null;
        $recipientType = $event->data['recipient_type'] ?? 'user';
        $recipientId = $event->data['recipient_id'] ?? $event->data['email'] ?? $event->data['phone'] ?? null;

        if (! $orderId || ! $recipientId) {
            return null;
        }

        return 'notification_sent:'.sha1(implode('|', [
            $event->trigger,
            $template->id,
            $template->channel,
            $recipientType,
            $recipientId,
            $orderId,
            $event->data['status'] ?? $event->data['order_status'] ?? '',
        ]));
    }

    private function releaseDuplicateKey(?string $key): void
    {
        if ($key) {
            Cache::forget($key);
        }
    }

    private function wasAlreadySent(NotificationTemplate $template, array $data, string $channel, string $message): bool
    {
        if (! Schema::hasTable('notification_logs')) {
            return false;
        }

        $orderId = $data['order_id'] ?? null;
        if (! $orderId) {
            return false;
        }

        return NotificationLog::query()
            ->where('template_id', $template->id)
            ->where('recipient_type', $data['recipient_type'] ?? 'user')
            ->where('recipient_id', $data['recipient_id'] ?? null)
            ->where('channel', $channel)
            ->where('status', 'sent')
            ->where('message', $message)
            ->exists();
    }
}
