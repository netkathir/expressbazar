<?php

namespace App\Http\Controllers;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    public function createLogin()
    {
        return view('storefront.auth.login', [
            'title' => 'Customer Login',
        ]);
    }

    public function createRegister()
    {
        return view('storefront.auth.register', [
            'title' => 'Create Account',
        ]);
    }

    public function storeRegister(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $otp = $this->issueOtp($user, 'register');

        session()->put('customer.pending_email', $user->email);
        session()->put('customer.otp_purpose', 'register');
        session()->put('customer.otp_preview', app()->isLocal() ? $otp : null);

        return redirect()->route('storefront.otp.form')->with('success', 'We sent an OTP to your email.');
    }

    public function storeLogin(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 'active'])) {
            return back()->withErrors([
                'email' => 'Invalid credentials or inactive account.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->role !== 'customer') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Please use a customer account to access the storefront.',
            ])->onlyInput('email');
        }

        if (! $user->email_verified_at) {
            $otp = $this->issueOtp($user, 'login');
            session()->put('customer.pending_email', $user->email);
            session()->put('customer.otp_purpose', 'login');
            session()->put('customer.otp_preview', app()->isLocal() ? $otp : null);
            Auth::logout();

            return redirect()->route('storefront.otp.form')->with('success', 'Please verify your email to continue.');
        }

        $request->session()->regenerate();

        return redirect()->route('user.home')->with('success', 'Welcome back.');
    }

    public function otpForm(Request $request)
    {
        $email = session('customer.pending_email');

        abort_if(! $email, 404);

        return view('storefront.auth.otp', [
            'title' => 'Verify OTP',
            'email' => $email,
            'otpPreview' => session('customer.otp_preview'),
            'purpose' => session('customer.otp_purpose', 'register'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp_code' => ['required', 'string', 'max:10'],
        ]);

        $otp = OtpVerification::query()
            ->where('email', $data['email'])
            ->where('otp_code', $data['otp_code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return back()->withErrors(['otp_code' => 'Invalid or expired OTP.'])->withInput();
        }

        $otp->update(['used_at' => now()]);

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();
        session()->forget(['customer.pending_email', 'customer.otp_preview', 'customer.otp_purpose']);

        return redirect()->route('user.home')->with('success', 'Email verified successfully.');
    }

    public function resendOtp(Request $request)
    {
        $email = session('customer.pending_email');

        abort_if(! $email, 404);

        $user = User::where('email', $email)->firstOrFail();
        $otp = $this->issueOtp($user, session('customer.otp_purpose', 'register'));

        session()->put('customer.otp_preview', app()->isLocal() ? $otp : null);

        return back()->with('success', 'A new OTP has been generated.');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('user.home')->with('success', 'You have been logged out.');
    }

    private function issueOtp(User $user, string $purpose): string
    {
        $otp = (string) random_int(100000, 999999);

        OtpVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => $purpose,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        try {
            Mail::raw("Your EXPRESS BAZAAR OTP is {$otp}. It expires in 5 minutes.", function ($message) use ($user) {
                $message->to($user->email)->subject('EXPRESS BAZAAR OTP');
            });
        } catch (\Throwable $throwable) {
            report($throwable);
        }

        return $otp;
    }
}
