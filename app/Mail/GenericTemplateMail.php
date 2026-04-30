<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $templateSubject, public string $templateMessage)
    {
    }

    public function build()
    {
        $fromAddress = $this->senderAddress();
        $replyToAddress = config('mail.from.address');

        $message = $this->from($fromAddress, config('mail.from.name', config('app.name', 'Express Bazar')))
            ->subject($this->templateSubject)
            ->withSymfonyMessage(function ($message) {
                $headers = $message->getHeaders();
                $headers->addTextHeader('X-Priority', '1');
                $headers->addTextHeader('X-MSMail-Priority', 'High');
                $headers->addTextHeader('Importance', 'High');
            })
            ->view('emails.generic-template')
            ->text('emails.generic-template-text')
            ->with([
                'messageBody' => $this->templateMessage,
            ]);

        if ($replyToAddress && filter_var($replyToAddress, FILTER_VALIDATE_EMAIL) && $replyToAddress !== $fromAddress) {
            $message->replyTo($replyToAddress, config('mail.from.name', config('app.name', 'Express Bazar')));
        }

        return $message;
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
}
