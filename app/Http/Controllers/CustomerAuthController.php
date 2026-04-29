<?php

namespace App\Http\Controllers;

use App\Mail\SendOtpMail;
use App\Models\OtpVerification;
use App\Models\Product;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            'password' => array_merge(PasswordService::rule(), ['confirmed']),
        ], PasswordService::validationMessages());

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
        $guestCart = $this->submittedGuestCart($request);

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
            if (! empty($guestCart)) {
                session()->put('customer.pending_guest_cart', $guestCart);
            }

            $otp = $this->issueOtp($user, 'login');
            session()->put('customer.pending_email', $user->email);
            session()->put('customer.otp_purpose', 'login');
            session()->put('customer.otp_preview', app()->isLocal() ? $otp : null);
            Auth::logout();

            return redirect()->route('storefront.otp.form')->with('success', 'Please verify your email to continue.');
        }

        $request->session()->regenerate();
        $guestCartMerged = $this->mergeGuestCartIntoSession($guestCart);

        return redirect()
            ->intended(route('user.home'))
            ->with('success', 'Welcome back.')
            ->with('guest_cart_merged', $guestCartMerged);
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
        $guestCartMerged = $this->mergeGuestCartIntoSession(session('customer.pending_guest_cart', []));
        session()->forget(['customer.pending_email', 'customer.otp_preview', 'customer.otp_purpose', 'customer.pending_guest_cart']);

        return redirect()
            ->intended(route('user.home'))
            ->with('success', 'Email verified successfully.')
            ->with('guest_cart_merged', $guestCartMerged);
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
            Mail::to($user->email)->send(new SendOtpMail($otp, [
                'recipient_type' => 'customer',
                'recipient_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'purpose' => $purpose,
            ]));
        } catch (\Exception $e) {
            report($e);
            Log::error('OTP Mail Error: '.$e->getMessage());
        }

        return $otp;
    }

    private function submittedGuestCart(Request $request): array
    {
        $rawCart = $request->input('guest_cart');

        if (blank($rawCart)) {
            return [];
        }

        $items = is_array($rawCart) ? $rawCart : json_decode((string) $rawCart, true);

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = max(1, min(99, (int) ($item['quantity'] ?? 1)));

            if ($productId <= 0) {
                continue;
            }

            $normalized[$productId] = [
                'product_id' => $productId,
                'quantity' => min(99, ($normalized[$productId]['quantity'] ?? 0) + $quantity),
            ];
        }

        return array_values($normalized);
    }

    private function mergeGuestCartIntoSession(array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $cart = session()->get('storefront.cart', []);
        $currentVendorId = $this->cartVendorId($cart);
        $products = Product::query()
            ->with(['vendor', 'inventory'])
            ->whereIn('id', collect($items)->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        $merged = false;

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product || $product->status !== 'active' || $product->vendor?->status !== 'active') {
                continue;
            }

            $productVendorId = (int) $product->vendor_id;

            if (! empty($cart) && $currentVendorId && $productVendorId !== $currentVendorId) {
                continue;
            }

            $requestedQuantity = max(1, min(99, (int) $item['quantity']));
            $currentQuantity = (int) ($cart[$product->id]['quantity'] ?? 0);
            $newQuantity = min(99, $currentQuantity + $requestedQuantity);

            if ($product->inventory?->inventory_mode === 'internal') {
                $available = (int) $product->inventory->stock_quantity;

                if ($available <= 0) {
                    continue;
                }

                $newQuantity = min($newQuantity, $available);
            }

            $cart[$product->id] = ['quantity' => $newQuantity];
            $currentVendorId = $productVendorId;
            $merged = true;
        }

        if (! $merged) {
            return false;
        }

        session()->put('storefront.cart', $cart);
        if ($currentVendorId) {
            session()->put('storefront.cart_vendor_id', $currentVendorId);
        }

        return true;
    }

    private function cartVendorId(array $cart): ?int
    {
        $vendorId = session('storefront.cart_vendor_id');

        if ($vendorId) {
            return (int) $vendorId;
        }

        $firstProductId = array_key_first($cart);

        return $firstProductId ? (int) Product::query()->whereKey($firstProductId)->value('vendor_id') : null;
    }
}
