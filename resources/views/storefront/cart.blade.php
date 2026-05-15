@extends('layouts.storefront')

@section('content')
    @php
        $canCheckout = auth()->check() && auth()->user()->role === 'customer';
        $offerSavings = \App\Support\StoreOfferPricing::cartSavings($cartItems);
    @endphp
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs"><a href="{{ route('user.home') }}">Home</a> <span>&rsaquo;</span> Cart</nav>
            <div class="sf-cart-page">
                <div class="sf-info-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h3 class="mb-0">Your Cart</h3>
                        <button type="button" class="btn btn-outline-dark rounded-pill btn-sm js-clear-cart">Clear cart</button>
                    </div>
                    <div data-cart-items>
                    @forelse ($cartItems as $item)
                        @php
                            $baseUnit = \App\Support\StoreOfferPricing::cartItemBaseUnit($item);
                            $offerUnit = \App\Support\StoreOfferPricing::cartItemOfferUnit($item);
                            $itemSavings = \App\Support\StoreOfferPricing::cartItemSavings($item);
                            $discountLabel = \App\Support\StoreOfferPricing::discountLabel($item['product'], $baseUnit, $offerUnit);
                        @endphp
                        <div class="sf-cart-row" data-cart-row data-product="{{ $item['product']->id }}">
                            <img src="{{ $item['product']->images->first() ? asset($item['product']->images->first()->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $item['product']->product_name }}">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $item['product']->product_name }}</div>
                                <div class="text-secondary small">{{ $item['product']->vendor?->vendor_name }}</div>
                                <div class="text-secondary small">
                                    {{ $item['quantity'] }} x Offer price: <span class="fw-semibold text-success">{{ \App\Support\StoreCurrency::format($offerUnit, 0) }}</span>
                                    @if ($baseUnit > $offerUnit)
                                        <span class="text-decoration-line-through ms-1">{{ \App\Support\StoreCurrency::format($baseUnit, 0) }}</span>
                                    @endif
                                </div>
                                @if ($itemSavings > 0)
                                    <div class="small text-success">
                                        {{ $discountLabel ?? 'Offer applied' }}. You save {{ \App\Support\StoreCurrency::format($itemSavings, 0) }}.
                                    </div>
                                @endif
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">{{ \App\Support\StoreCurrency::format($item['subtotal'], 0) }}</div>
                                <div class="sf-stepper sf-stepper-sm mt-2">
                                    <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="-1" data-product="{{ $item['product']->id }}">−</button>
                                    <span class="sf-stepper-value" data-cart-stepper-value>{{ $item['quantity'] }}</span>
                                    <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="1" data-product="{{ $item['product']->id }}">+</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-empty-state>{{ config('ui_messages.empty_cart') }}</x-empty-state>
                    @endforelse
                    </div>
                </div>
                <div class="sf-info-card sf-cart-summary">
                    <h4 class="mb-3">Bill Summary</h4>
                    <div class="d-flex justify-content-between mb-2"><span>Item Total</span><strong data-cart-summary="itemTotal">{{ \App\Support\StoreCurrency::format($cartTotals['itemTotal'], 0) }}</strong></div>
                    @if ($offerSavings > 0)
                        <div class="d-flex justify-content-between mb-2 text-success"><span>Offer Savings</span><strong>{{ \App\Support\StoreCurrency::format($offerSavings, 0) }}</strong></div>
                    @endif
                    @if (($cartTotals['tax'] ?? 0) > 0)
                        <div class="d-flex justify-content-between mb-2"><span>Tax</span><strong data-cart-summary="tax">{{ \App\Support\StoreCurrency::format($cartTotals['tax'], 0) }}</strong></div>
                    @endif
                    <div class="d-flex justify-content-between mb-2"><span>Delivery Fee</span><strong data-cart-summary="delivery">{{ \App\Support\StoreCurrency::format($cartTotals['delivery'], 0) }}</strong></div>
                    <hr>
                    <div class="d-flex justify-content-between"><span class="fw-semibold">To Pay</span><strong class="fs-5" data-cart-summary="grandTotal">{{ \App\Support\StoreCurrency::format($cartTotals['grandTotal'], 0) }}</strong></div>
                    <a href="{{ route('storefront.checkout') }}" class="btn btn-danger w-100 rounded-pill mt-3 {{ $canCheckout ? '' : 'js-checkout-auth-required' }}">
                        @if ($canCheckout)
                            Proceed to Checkout
                        @else
                            Login to Checkout
                        @endif
                    </a>
                    <div class="text-secondary small mt-2">
                        @if ($canCheckout)
                            Exact delivery validation is checked before checkout.
                        @else
                            Please login or register before payment.
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
