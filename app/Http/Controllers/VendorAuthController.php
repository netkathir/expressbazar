<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VendorAuthController extends Controller
{
    public function create()
    {
        return view('vendor.auth.login', [
            'title' => 'Vendor Login',
        ]);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim((string) $credentials['email']));
        $vendor = Vendor::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $vendor || ! $vendor->password || ! Hash::check($credentials['password'], $vendor->password)) {
            Log::warning('Vendor login failed.', [
                'email' => $email,
                'vendor_found' => (bool) $vendor,
                'has_password' => (bool) ($vendor?->password),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Invalid vendor credentials.',
            ]);
        }

        if (! $vendor->isActive()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your vendor account is inactive. Please contact admin.',
            ]);
        }

        Auth::guard('vendor')->login($vendor, $request->boolean('remember'));
        $vendor->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route('vendor.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login');
    }
}
