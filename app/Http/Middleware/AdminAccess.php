<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Admin access only.');
        }

        $routeName = $request->route()?->getName();

        if ($routeName && str_starts_with($routeName, 'admin.') && ! in_array($routeName, ['admin.dashboard', 'admin.logout', 'admin.module'], true) && ! $user->canAccessAdminRoute($routeName, $request->method())) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
