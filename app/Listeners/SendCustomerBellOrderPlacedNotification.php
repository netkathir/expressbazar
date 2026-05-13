<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Support\NotificationHelper;

class SendCustomerBellOrderPlacedNotification
{
    public function handle(OrderPlaced $event): void
    {
        NotificationHelper::sendOrderPlaced($event->order);
    }
}
