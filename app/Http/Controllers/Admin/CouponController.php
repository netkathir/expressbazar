<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $coupons = Coupon::query()
            ->with('vendor')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('code', 'like', "%{$search}%");
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->when($request->filled('vendor_id'), fn ($query) => $query->where('vendor_id', $request->integer('vendor_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.coupons.index', [
            'title' => 'Coupon Management',
            'activeMenu' => 'coupons',
            'coupons' => $coupons,
            'vendors' => Vendor::orderBy('vendor_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.coupons.form', [
            'title' => 'Add Coupon',
            'activeMenu' => 'coupons',
            'coupon' => new Coupon(['is_active' => true]),
            'vendors' => Vendor::orderBy('vendor_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        Coupon::create($this->validateCoupon($request));

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.form', [
            'title' => 'Edit Coupon',
            'activeMenu' => 'coupons',
            'coupon' => $coupon,
            'vendors' => Vendor::orderBy('vendor_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validateCoupon($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully.');
    }

    private function validateCoupon(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['min_order'] = $data['min_order'] ?? null;
        $data['vendor_id'] = $data['vendor_id'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        if ($data['type'] === 'percentage' && (float) $data['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'Percentage coupon cannot exceed 100%.']);
        }

        return $data;
    }
}
