<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericTemplateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $templateSubject, public string $templateMessage)
    {
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('app.name', 'Express Bazar'))
            ->subject($this->templateSubject)
            ->view('emails.generic-template')
            ->with([
                'messageBody' => $this->templateMessage,
            ]);
    }
}
