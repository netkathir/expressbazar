<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();

        $coupons = Coupon::query()
            ->with('vendor')
            ->where('vendor_id', $vendor->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('code', 'like', "%{$search}%");
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.coupons.index', [
            'title' => 'Coupon Management',
            'activeMenu' => 'coupons',
            'coupons' => $coupons,
            'vendors' => collect([$vendor]),
            'routePrefix' => 'vendor.coupons',
            'isVendorPanel' => true,
        ]);
    }

    public function create()
    {
        return view('admin.coupons.form', [
            'title' => 'Add Coupon',
            'activeMenu' => 'coupons',
            'coupon' => new Coupon(['is_active' => true]),
            'vendors' => collect([Auth::guard('vendor')->user()]),
            'mode' => 'create',
            'routePrefix' => 'vendor.coupons',
            'isVendorPanel' => true,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCoupon($request);
        $data['vendor_id'] = Auth::guard('vendor')->id();

        Coupon::create($data);

        return redirect()->route('vendor.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        $this->authorizeVendorCoupon($coupon);

        return view('admin.coupons.form', [
            'title' => 'Edit Coupon',
            'activeMenu' => 'coupons',
            'coupon' => $coupon,
            'vendors' => collect([Auth::guard('vendor')->user()]),
            'mode' => 'edit',
            'routePrefix' => 'vendor.coupons',
            'isVendorPanel' => true,
        ]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $this->authorizeVendorCoupon($coupon);
        $data = $this->validateCoupon($request, $coupon);
        $data['vendor_id'] = Auth::guard('vendor')->id();

        $coupon->update($data);

        return redirect()->route('vendor.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->authorizeVendorCoupon($coupon);
        $coupon->delete();

        return redirect()->route('vendor.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    private function authorizeVendorCoupon(Coupon $coupon): void
    {
        abort_if((int) $coupon->vendor_id !== (int) Auth::guard('vendor')->id(), 404);
    }

    private function validateCoupon(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['min_order'] = $data['min_order'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        if ($data['type'] === 'percentage' && (float) $data['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'Percentage coupon cannot exceed 100%.']);
        }

        return $data;
    }
}
