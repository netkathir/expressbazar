@extends('layouts.admin')

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'admin.coupons';
        $isVendorPanel = $isVendorPanel ?? false;
    @endphp
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Coupon' : 'Edit Coupon' }}</h1>
                </div>
                <a href="{{ route($routePrefix.'.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route($routePrefix.'.store') : route($routePrefix.'.update', $coupon) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-4">
                    <label class="form-label">Coupon Code</label>
                    <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-control text-uppercase" placeholder="WELCOME10" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Type</label>
                    <select name="type" class="form-select" required>
                        <option value="percentage" @selected(old('type', $coupon->type ?: 'percentage') === 'percentage')>Percentage</option>
                        <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed Amount</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Value</label>
                    <input type="number" name="value" step="0.01" min="0.01" value="{{ old('value', $coupon->value) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimum Order</label>
                    <input type="number" name="min_order" step="0.01" min="0" value="{{ old('min_order', $coupon->min_order) }}" class="form-control" placeholder="Optional">
                </div>
                @if ($isVendorPanel)
                    <input type="hidden" name="vendor_id" value="{{ auth('vendor')->id() }}">
                @else
                    <div class="col-md-4">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">All vendors</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $coupon->vendor_id) === (string) $vendor->id)>{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label">Expiry Date & Time</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d\TH:i')) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="coupon-active" name="is_active" value="1" @checked(old('is_active', $coupon->exists ? $coupon->is_active : true))>
                        <label class="form-check-label" for="coupon-active">Active</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Coupon' : 'Update Coupon' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
