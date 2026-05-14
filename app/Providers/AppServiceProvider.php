<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\TriggerNotificationEvent;
use App\Listeners\DispatchOrderPlacedTemplateNotification;
use App\Listeners\SendCustomerBellOrderPlacedNotification;
use App\Listeners\SendTemplateNotification;
use App\Listeners\SendVendorNotification;
use App\Support\StorefrontLayoutData;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
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
        Event::listen(OrderPlaced::class, SendCustomerBellOrderPlacedNotification::class);
        Event::listen(OrderPlaced::class, SendVendorNotification::class);
        Event::listen(TriggerNotificationEvent::class, SendTemplateNotification::class);

        Blade::if('canRoute', function (?string $routeName, string $method = 'GET'): bool {
            $vendor = auth('vendor')->user();
            $admin = auth()->user();

            if ($vendor && method_exists($vendor, 'canAccessVendorRoute')) {
                return $vendor->canAccessVendorRoute($routeName, $method);
            }

            if ($admin && method_exists($admin, 'canAccessAdminRoute')) {
                return $admin->canAccessAdminRoute($routeName, $method);
            }

            return false;
        });

        Blade::if('canPermission', function (string $permission): bool {
            $user = auth()->user() ?: auth('vendor')->user();

            return $user && method_exists($user, 'hasPermission') && $user->hasPermission($permission);
        });

        View::composer('layouts.storefront', function ($view): void {
            $view->with(app(StorefrontLayoutData::class)->defaults($view->getData()));
        });
    }
}
