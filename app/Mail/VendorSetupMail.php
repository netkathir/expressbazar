<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Vendor $vendor, public string $setupUrl)
    {
    }

    public function build()
    {
        return $this->from($this->senderAddress(), config('mail.from.name', config('app.name', 'Express Bazar')))
            ->subject('Complete your Express Bazar vendor setup')
            ->view('emails.vendor-setup')
            ->text('emails.vendor-setup-text')
            ->with([
                'vendor' => $this->vendor,
                'setupUrl' => $this->setupUrl,
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
