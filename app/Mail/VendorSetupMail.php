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
        return $this->from(config('mail.from.address'), config('mail.from.name', config('app.name', 'Express Bazar')))
            ->subject('Complete your Express Bazar vendor setup')
            ->view('emails.vendor-setup')
            ->text('emails.vendor-setup-text')
            ->with([
                'vendor' => $this->vendor,
                'setupUrl' => $this->setupUrl,
            ]);
    }
}
