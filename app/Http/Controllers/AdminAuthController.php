<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function create()
    {
        return view('admin.auth.login', [
            'title' => 'Admin Login',
        ]);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        $loginValue = trim($credentials['email']);
        $loginField = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([
            $loginField => $loginValue,
            'password' => $credentials['password'],
            'status' => 'active',
        ], $remember)) {
            $request->session()->regenerate();

            $user = $request->user();

            if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'This account is not allowed to access the admin panel.'])
                    ->onlyInput('email');
            }

            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            return redirect()->route('admin.dashboard');
        }

        return back()
            ->withErrors(['email' => 'Invalid admin credentials or account is inactive.'])
            ->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
