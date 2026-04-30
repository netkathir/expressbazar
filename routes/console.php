<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:auto-cancel-pending {minutes=30}', function ($minutes) {
    $minutes = max(1, (int) $minutes);
    $count = app(\App\Services\VendorOrderWorkflowService::class)->autoCancelPending($minutes);

    $this->info("Auto cancelled {$count} pending orders.");
})->purpose('Auto cancel pending vendor orders after the configured minutes');

Schedule::command('orders:auto-cancel-pending 30')->everyFiveMinutes();
