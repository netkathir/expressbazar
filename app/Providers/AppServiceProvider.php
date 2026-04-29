<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\TriggerNotificationEvent;
use App\Listeners\DispatchOrderPlacedTemplateNotification;
use App\Listeners\SendTemplateNotification;
use App\Listeners\SendVendorNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Event::listen(OrderPlaced::class, SendVendorNotification::class);
        Event::listen(OrderPlaced::class, DispatchOrderPlacedTemplateNotification::class);
        Event::listen(TriggerNotificationEvent::class, SendTemplateNotification::class);
    }
}
