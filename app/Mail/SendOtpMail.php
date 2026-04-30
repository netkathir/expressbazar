<?php

namespace App\Mail;

use App\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public array $templateData;
    public ?string $templateMessage = null;
    public ?string $templateSubject = null;

    public function __construct($otp, array $templateData = [])
    {
        $this->otp = $otp;
        $this->templateData = array_merge([
            'otp' => $otp,
            'otp_code' => $otp,
            'code' => $otp,
            'expires_in' => '5 minutes',
            'expiry_time' => '5',
            'app_name' => config('app.name', 'Express Bazar'),
        ], $templateData);
    }

    public function build()
    {
        $template = app(NotificationTemplateService::class)->findActive('otp mail', 'email');
        $fromAddress = $this->senderAddress();

        if ($template) {
            $this->templateSubject = app(NotificationTemplateService::class)
                ->render($template->subject ?: $template->template_name, $this->templateData);
            $this->templateMessage = app(NotificationTemplateService::class)
                ->render($template->message_body, $this->templateData);

            return $this->from($fromAddress, config('mail.from.name', config('app.name', 'Express Bazar')))
                ->subject($this->templateSubject)
                ->withSymfonyMessage(fn ($message) => $this->addPriorityHeaders($message))
                ->view('emails.generic-template')
                ->text('emails.generic-template-text')
                ->with([
                    'messageBody' => $this->templateMessage,
                ]);
        }

        return $this->from($fromAddress, config('mail.from.name', 'OTP Service'))
            ->subject('Your OTP Code')
            ->withSymfonyMessage(fn ($message) => $this->addPriorityHeaders($message))
            ->view('emails.otp');
    }

    private function senderAddress(): string
    {
        $configuredFrom = config('mail.from.address');
        $smtpUsername = config('mail.mailers.smtp.username');

        if (
            config('mail.default') === 'smtp'
            && $smtpUsername
            && filter_var($smtpUsername, FILTER_VALIDATE_EMAIL)
        ) {
            return $smtpUsername;
        }

        return $configuredFrom;
    }

    private function addPriorityHeaders($message): void
    {
        $headers = $message->getHeaders();
        $headers->addTextHeader('X-Priority', '1');
        $headers->addTextHeader('X-MSMail-Priority', 'High');
        $headers->addTextHeader('Importance', 'High');
    }
}
