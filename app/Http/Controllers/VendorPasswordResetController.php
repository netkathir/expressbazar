<?php

namespace App\Http\Controllers;

use App\Mail\SendOtpMail;
use App\Models\Vendor;
use App\Models\VendorPasswordOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class VendorPasswordResetController extends Controller
{
    public function create()
    {
        return view('vendor.auth.forgot-password', [
            'title' => 'Forgot Vendor Password',
        ]);
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::exists('vendors', 'email')->where(fn ($query) => $query->where('status', 'active')),
            ],
        ]);

        $vendor = Vendor::query()
            ->whereRaw('LOWER(email) = ?', [$this->lower($data['email'])])
            ->where('status', 'active')
            ->firstOrFail();

        $otp = (string) random_int(100000, 999999);

        $record = VendorPasswordOtp::updateOrCreate(
            ['email' => $vendor->email],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5),
            ]
        );

        session()->put('vendor_password_reset.pending_email', $vendor->email);
        session()->forget('vendor_password_reset.verified_email');

        try {
            Mail::to($vendor->email)->send(new SendOtpMail($otp, [
                'recipient_type' => 'vendor',
                'recipient_id' => $vendor->id,
                'name' => $vendor->vendor_name,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'purpose' => 'vendor_password_reset',
            ]));
        } catch (\Exception $exception) {
            $record->delete();
            report($exception);
            Log::error('Vendor password reset OTP mail error: '.$exception->getMessage());

            return back()
                ->withInput()
                ->withErrors(['email' => 'We could not send the vendor reset OTP right now. Please try again in a moment.']);
        }

        return redirect()
            ->route('vendor.password.otp.form')
            ->with('success', 'OTP sent to your email.');
    }

    public function otpForm()
    {
        $email = session('vendor_password_reset.pending_email');

        abort_if(! $email, 404);

        return view('vendor.auth.forgot-password-otp', [
            'title' => 'Verify Vendor OTP',
            'email' => $email,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp_code' => ['required', 'string', 'max:10'],
        ]);

        $record = VendorPasswordOtp::query()
            ->where('email', $data['email'])
            ->where('otp', $data['otp_code'])
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return back()->withErrors(['otp_code' => 'Invalid or expired OTP.'])->withInput();
        }

        session()->put('vendor_password_reset.verified_email', $data['email']);
        VendorPasswordOtp::where('email', $data['email'])->delete();

        return redirect()->route('vendor.password.reset.form')->with('success', 'OTP verified successfully.');
    }

    public function createResetForm()
    {
        $email = session('vendor_password_reset.verified_email');

        abort_if(! $email, 404);

        return view('vendor.auth.reset-password', [
            'title' => 'Reset Vendor Password',
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $email = session('vendor_password_reset.verified_email');

        abort_if(! $email, 404);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ], [
            'password.regex' => 'Password must include uppercase, lowercase, number and special character.',
        ]);

        if ($data['email'] !== $email) {
            return back()->withErrors(['email' => 'Your reset session has expired. Please request a new OTP.'])->withInput();
        }

        Vendor::where('email', $email)
            ->where('status', 'active')
            ->update([
                'password' => Hash::make($data['password']),
            ]);

        VendorPasswordOtp::where('email', $email)->delete();
        session()->forget(['vendor_password_reset.pending_email', 'vendor_password_reset.verified_email']);

        return redirect()->route('vendor.login')->with('success', 'Vendor password reset successful. Please login again.');
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower(trim($value)) : strtolower(trim($value));
    }
}
