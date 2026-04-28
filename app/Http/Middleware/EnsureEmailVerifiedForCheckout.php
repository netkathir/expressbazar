<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerifiedForCheckout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $this->hasVerifiedEmail($user)) {
            return back()->withErrors('Please verify your email to place orders');
        }

        return $next($request);
    }

    private function hasVerifiedEmail(object $user): bool
    {
        $attributes = method_exists($user, 'getAttributes') ? $user->getAttributes() : [];

        if (array_key_exists('email_verified', $attributes)) {
            return (bool) $user->getAttribute('email_verified');
        }

        return ! empty($user->getAttribute('email_verified_at'));
    }
}
