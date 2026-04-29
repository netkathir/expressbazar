<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        Log::info('SMS notification requested.', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }
}
