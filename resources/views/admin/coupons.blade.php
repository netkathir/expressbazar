@extends('admin.layout')

@section('content')
    <div class="hero-panel" style="margin-bottom: 16px;">
        <div>
            <h1 style="margin: 0 0 8px;">Coupons</h1>
            <p class="admin-muted" style="margin: 0;">Create discounts for launches, festivals, and repeat buying.</p>
        </div>
        <a class="btn btn-primary" href="#">Create Coupon</a>
    </div>

    <section class="admin-grid cols-3">
        @foreach ($coupons as $coupon)
            <div class="admin-card soft">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap: 12px;">
                    <div>
                        <div class="admin-badge">{{ $coupon['code'] }}</div>
                        <h3 style="margin: 14px 0 8px;">{{ $coupon['type'] }} discount</h3>
                        <p class="admin-muted" style="margin: 0;">Used by {{ $coupon['orders'] }} orders</p>
                    </div>
                    <span class="admin-badge {{ $coupon['status'] === 'active' ? 'success' : 'warning' }}">{{ ucfirst($coupon['status']) }}</span>
                </div>
                <div class="value" style="font-size: 2rem; margin-top: 18px;">{{ $coupon['value'] }}</div>
            </div>
        @endforeach
    </section>
@endsection
