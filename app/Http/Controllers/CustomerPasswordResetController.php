<?php

namespace App\Http\Controllers;

use App\Mail\SendOtpMail;
use App\Models\PasswordOtp;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CustomerPasswordResetController extends Controller
{
    public function create()
    {
        return view('storefront.auth.forgot-password', [
            'title' => 'Forgot Password',
        ]);
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email')->where(function ($query) {
                    $query->where('role', 'customer')->where('status', 'active');
                }),
            ],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->where('role', 'customer')
            ->where('status', 'active')
            ->firstOrFail();

        $otp = (string) random_int(100000, 999999);

        $record = PasswordOtp::updateOrCreate(
            ['email' => $user->email],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5),
            ]
        );

        session()->put('password_reset.pending_email', $user->email);
        session()->forget('password_reset.verified_email');

        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, [
                'recipient_type' => 'customer',
                'recipient_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'purpose' => 'password_reset',
            ]));
        } catch (\Exception $e) {
            $record->delete();
            report($e);
            Log::error('Password reset OTP mail error: '.$e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['email' => 'We could not send the reset OTP right now. Please try again in a moment.']);
        }

        return redirect()
            ->route('storefront.password.otp.form')
            ->with('success', 'OTP sent to your email.');
    }

    public function otpForm(Request $request)
    {
        $email = session('password_reset.pending_email');

        abort_if(! $email, 404);

        return view('storefront.auth.forgot-password-otp', [
            'title' => 'Verify Password OTP',
            'email' => $email,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp_code' => ['required', 'string', 'max:10'],
        ]);

        $record = PasswordOtp::query()
            ->where('email', $data['email'])
            ->where('otp', $data['otp_code'])
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return back()->withErrors(['otp_code' => 'Invalid or expired OTP.'])->withInput();
        }

        session()->put('password_reset.verified_email', $data['email']);
        PasswordOtp::where('email', $data['email'])->delete();

        return redirect()->route('storefront.password.reset.form')->with('success', 'OTP verified successfully.');
    }

    public function createResetForm(Request $request)
    {
        $email = session('password_reset.verified_email');

        abort_if(! $email, 404);

        return view('storefront.auth.reset-password', [
            'title' => 'Reset Password',
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $email = session('password_reset.verified_email');

        abort_if(! $email, 404);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => array_merge(PasswordService::rule(), ['confirmed']),
        ], PasswordService::validationMessages());

        if ($data['email'] !== $email) {
            return back()->withErrors(['email' => 'Your reset session has expired. Please request a new OTP.'])->withInput();
        }

        User::where('email', $email)->update([
            'password' => Hash::make($data['password']),
        ]);

        PasswordOtp::where('email', $email)->delete();
        session()->forget(['password_reset.pending_email', 'password_reset.verified_email']);

        return redirect()->route('storefront.login')->with('success', 'Password reset successful. Please login again.');
    }
}
