<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Vendor $vendor, public string $plainPassword)
    {
    }

    public function build()
    {
        $fromAddress = $this->senderAddress();
        $replyToAddress = config('mail.from.address');

        $message = $this->from($fromAddress, config('mail.from.name', config('app.name', 'Express Bazar')))
            ->subject('Your Express Bazar vendor panel access')
            ->view('emails.vendor-credentials')
            ->text('emails.vendor-credentials-text')
            ->with([
                'vendor' => $this->vendor,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => route('vendor.login'),
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
