<?php

namespace App\Listeners;

use App\Events\TriggerNotificationEvent;
use App\Mail\GenericTemplateMail;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendTemplateNotification implements ShouldQueue
{
    public function __construct(private NotificationTemplateService $templates)
    {
    }

    public function handle(TriggerNotificationEvent $event): void
    {
        $template = $this->templates->findActive($event->trigger, $event->data['channel'] ?? null);

        if (! $template) {
            return;
        }

        $message = $this->templates->render($template->message_body, $event->data);

        try {
            if ($template->channel === 'email') {
                $this->sendEmail($template, $message, $event->data);
                $this->logNotification($template->id, $event->data, 'email', $message, 'sent');
                return;
            }

            if ($template->channel === 'sms') {
                $this->sendSms($message, $event->data);
                $this->logNotification($template->id, $event->data, 'sms', $message, 'sent');
            }
        } catch (Throwable $exception) {
            Log::error('Template notification failed.', [
                'trigger' => $event->trigger,
                'template_id' => $template->id,
                'channel' => $template->channel,
                'error' => $exception->getMessage(),
            ]);

            $this->logNotification($template->id, $event->data, $template->channel, $message, 'failed', $exception->getMessage());
        }
    }

    private function sendEmail(NotificationTemplate $template, string $message, array $data): void
    {
        $email = $data['email'] ?? null;

        if (! $email) {
            throw new \RuntimeException('Email recipient missing.');
        }

        $subject = $this->templates->render($template->subject ?: $template->template_name, $data);

        Mail::to($email)->queue(new GenericTemplateMail($subject, $message));
    }

    private function sendSms(string $message, array $data): void
    {
        $phone = $data['phone'] ?? null;

        if (! $phone) {
            throw new \RuntimeException('SMS recipient missing.');
        }

        app(SmsService::class)->send($phone, $message);
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
}
