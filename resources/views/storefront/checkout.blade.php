@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">Home <span>›</span> Checkout</nav>
            <div class="sf-cart-page">
                <div class="sf-info-card">
                    <h3 class="mb-3">Delivery Address</h3>
                    @forelse ($addresses as $address)
                        <label class="sf-sidepanel p-3 mb-3 d-block">
                            <div class="d-flex gap-2 align-items-start">
                                <input type="radio" name="address_id" class="mt-1">
                                <div>
                                    <div class="fw-semibold">{{ $address->label ?: $address->recipient_name }}</div>
                                    <div class="small text-secondary">{{ $address->address_line_1 }}, {{ $address->city?->city_name }}</div>
                                    <div class="small text-secondary">{{ $address->zone?->zone_name ?? '-' }} | {{ $address->postcode }}</div>
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="sf-empty-state mb-3">No saved address yet. Add one from My Account.</div>
                    @endforelse
                    <a href="{{ route('storefront.account') }}" class="btn btn-outline-dark rounded-pill">Manage Addresses</a>
                </div>
                <div class="sf-info-card sf-cart-summary">
                    <h4 class="mb-3">Order Summary</h4>
                    @foreach ($cartItems as $item)
                        <div class="d-flex justify-content-between small mb-2">
                            <span>{{ $item['product']->product_name }} x {{ $item['quantity'] }}</span>
                            <strong>₹{{ number_format($item['subtotal'], 0) }}</strong>
                        </div>
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between mb-2"><span>Item Total</span><strong>₹{{ number_format($cartTotals['itemTotal'], 0) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Delivery Fee</span><strong>₹{{ number_format($cartTotals['delivery'], 0) }}</strong></div>
                    <div class="d-flex justify-content-between"><span class="fw-semibold">To Pay</span><strong class="fs-5">₹{{ number_format($cartTotals['grandTotal'], 0) }}</strong></div>
                    <button class="btn btn-danger w-100 rounded-pill mt-3" disabled>Place Order</button>
                    <div class="text-secondary small mt-2">Order placement and payment are the next step.</div>
                </div>
            </div>
        </section>
    </main>
@endsection
