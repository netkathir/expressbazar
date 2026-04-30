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
        return $this->from(config('mail.from.address'), config('app.name', 'Express Bazar'))
            ->subject($this->templateSubject)
            ->withSymfonyMessage(function ($message) {
                $headers = $message->getHeaders();
                $headers->addTextHeader('X-Priority', '1');
                $headers->addTextHeader('X-MSMail-Priority', 'High');
                $headers->addTextHeader('Importance', 'High');
            })
            ->view('emails.generic-template')
            ->with([
                'messageBody' => $this->templateMessage,
            ]);
    }
}
