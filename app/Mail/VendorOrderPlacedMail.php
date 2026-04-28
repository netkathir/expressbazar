<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorOrderPlacedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('app.name', 'Express Bazar'))
            ->subject('New Order Received - '.$this->order->order_number)
            ->view('emails.vendor-order-placed')
            ->with([
                'order' => $this->order,
            ]);
    }
}
