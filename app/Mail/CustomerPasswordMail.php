<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $password
    ) {
    }

    public function build()
    {
        return $this->from($this->senderAddress(), config('mail.from.name', config('app.name', 'Express Bazar')))
            ->subject('Your Account Password')
            ->view('emails.customer-password')
            ->with([
                'name' => $this->user->name,
                'password' => $this->password,
            ]);
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
