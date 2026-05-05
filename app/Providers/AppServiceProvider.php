<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\TriggerNotificationEvent;
use App\Listeners\DispatchOrderPlacedTemplateNotification;
use App\Listeners\SendTemplateNotification;
use App\Listeners\SendVendorNotification;
use App\Support\StorefrontLayoutData;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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

        Event::listen(OrderPlaced::class, DispatchOrderPlacedTemplateNotification::class);
        Event::listen(OrderPlaced::class, SendVendorNotification::class);
        Event::listen(TriggerNotificationEvent::class, SendTemplateNotification::class);

        View::composer('layouts.storefront', function ($view): void {
            $view->with(app(StorefrontLayoutData::class)->defaults($view->getData()));
        });
    }
}
