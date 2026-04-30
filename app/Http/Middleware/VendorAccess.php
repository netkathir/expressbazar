<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VendorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('vendor')->check()) {
            return redirect()->route('vendor.login');
        }

        $vendor = Auth::guard('vendor')->user();

        if (! $vendor || ! $vendor->isActive()) {
            Auth::guard('vendor')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('vendor.login')
                ->withErrors(['email' => 'Your vendor account is inactive. Please contact admin.']);
        }

        $routeName = $request->route()?->getName();

        if (
            $routeName
            && str_starts_with($routeName, 'vendor.')
            && ! in_array($routeName, ['vendor.dashboard', 'vendor.logout'], true)
            && ! $vendor->canAccessVendorRoute($routeName, $request->method())
        ) {
            abort(403, 'You do not have permission to access this vendor module.');
        }

        return $next($request);
    }
}
