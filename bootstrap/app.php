<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminAccess;
use App\Http\Middleware\EnsureEmailVerifiedForCheckout;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\VendorAccess;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => request()->is('vendor*') ? route('vendor.login') : (request()->is('admin*') ? route('admin.login') : route('storefront.login')));
        $middleware->redirectUsersTo(fn () => auth('vendor')->check() ? route('vendor.dashboard') : (auth()->user()?->role === 'customer' ? route('user.home') : route('admin.dashboard')));

        $middleware->alias([
            'admin' => AdminAccess::class,
            'permission' => PermissionMiddleware::class,
            'vendor' => VendorAccess::class,
            'verify.checkout.email' => EnsureEmailVerifiedForCheckout::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'otp/verify',
            'otp/resend',
            'forgot-password/verify-otp',
            'forgot-password/send-otp',
            'admin/forgot-password/verify-otp',
            'admin/forgot-password/send-otp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
