@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">Home <span>›</span> Cart</nav>
            <div class="sf-cart-page">
                <div class="sf-info-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h3 class="mb-0">Your Cart</h3>
                        <button type="button" class="btn btn-outline-dark rounded-pill btn-sm js-clear-cart">Clear cart</button>
                    </div>
                    @forelse ($cartItems as $item)
                        <div class="sf-cart-row">
                            <img src="{{ $item['product']->images->first() ? asset($item['product']->images->first()->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $item['product']->product_name }}">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $item['product']->product_name }}</div>
                                <div class="text-secondary small">{{ $item['product']->vendor?->vendor_name }}</div>
                                <div class="text-secondary small">{{ $item['quantity'] }} x ₹{{ number_format($item['unit_price'], 0) }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">₹{{ number_format($item['subtotal'], 0) }}</div>
                                <div class="sf-stepper sf-stepper-sm mt-2">
                                    <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="-1" data-product="{{ $item['product']->id }}">−</button>
                                    <span class="sf-stepper-value">{{ $item['quantity'] }}</span>
                                    <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="1" data-product="{{ $item['product']->id }}">+</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sf-empty-state">Your cart is empty</div>
                    @endforelse
                </div>
                <div class="sf-info-card sf-cart-summary">
                    <h4 class="mb-3">Bill Summary</h4>
                    <div class="d-flex justify-content-between mb-2"><span>Item Total</span><strong>₹{{ number_format($cartTotals['itemTotal'], 0) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Delivery Fee</span><strong>₹{{ number_format($cartTotals['delivery'], 0) }}</strong></div>
                    <hr>
                    <div class="d-flex justify-content-between"><span class="fw-semibold">To Pay</span><strong class="fs-5">₹{{ number_format($cartTotals['grandTotal'], 0) }}</strong></div>
                    <a href="{{ route('storefront.checkout') }}" class="btn btn-danger w-100 rounded-pill mt-3">Proceed to Checkout</a>
                    <div class="text-secondary small mt-2">Exact delivery validation is checked before checkout.</div>
                </div>
            </div>
        </section>
    </main>
@endsection
