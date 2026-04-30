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
        return $this->from(config('mail.from.address'), config('mail.from.name', config('app.name', 'Express Bazar')))
            ->subject('Your Express Bazar vendor panel access')
            ->view('emails.vendor-credentials')
            ->with([
                'vendor' => $this->vendor,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => route('vendor.login'),
            ]);
    }
}
