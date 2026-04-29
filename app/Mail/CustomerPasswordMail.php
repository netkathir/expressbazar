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
        return $this->from(config('mail.from.address'), config('mail.from.name', config('app.name', 'Express Bazar')))
            ->subject('Your Account Password')
            ->view('emails.customer-password')
            ->with([
                'name' => $this->user->name,
                'password' => $this->password,
            ]);
    }
}
