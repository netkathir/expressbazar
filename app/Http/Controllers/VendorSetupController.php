<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class VendorSetupController extends Controller
{
    public function edit(string $token)
    {
        $vendor = Vendor::query()
            ->where('setup_token', $token)
            ->firstOrFail();

        return view('vendor.auth.setup', [
            'title' => 'Vendor Setup',
            'vendor' => $vendor,
            'token' => $token,
        ]);
    }

    public function update(Request $request, string $token)
    {
        $vendor = Vendor::query()
            ->where('setup_token', $token)
            ->firstOrFail();

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $vendor->forceFill([
            'password' => Hash::make($data['password']),
            'setup_token' => null,
            'is_setup_complete' => true,
        ])->save();

        return redirect()
            ->route('vendor.login')
            ->with('success', 'Vendor setup completed. Please login with your new password.');
    }
}
