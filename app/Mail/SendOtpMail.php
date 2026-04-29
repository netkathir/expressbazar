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

        if ($template) {
            $this->templateSubject = app(NotificationTemplateService::class)
                ->render($template->subject ?: $template->template_name, $this->templateData);
            $this->templateMessage = app(NotificationTemplateService::class)
                ->render($template->message_body, $this->templateData);

            return $this->from(config('mail.from.address'), config('mail.from.name', config('app.name', 'Express Bazar')))
                ->subject($this->templateSubject)
                ->view('emails.generic-template')
                ->with([
                    'messageBody' => $this->templateMessage,
                ]);
        }

        return $this->from(config('mail.from.address'), 'OTP Service')
            ->subject('Your OTP Code')
            ->view('emails.otp');
    }
}
