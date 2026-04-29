<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TriggerNotificationEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $trigger, public array $data = [])
    {
    }
}
