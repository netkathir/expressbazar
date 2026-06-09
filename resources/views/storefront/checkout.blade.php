@extends('layouts.storefront')

@section('content')
    @php
        $offerSavings = \App\Support\StoreOfferPricing::cartSavings($cartItems);
        $addressesUrl = \Illuminate\Support\Facades\Route::has('storefront.addresses.index')
            ? route('storefront.addresses.index')
            : url('/account/addresses');
    @endphp
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs"><a href="{{ route('user.home') }}">Home</a> <span>&rsaquo;</span> Checkout</nav>

            @if ($cartItems->isEmpty())
                <x-empty-state>{{ config('ui_messages.empty_cart') }}</x-empty-state>
            @else
                <form method="POST" action="{{ route('storefront.checkout.place') }}">
                    @csrf
                    <div class="sf-cart-page">
                        <div class="sf-info-card">
                            <h3 class="mb-3">Delivery Address</h3>
                            @forelse ($addresses as $address)
                                @php
                                    $deliveryCharge = (float) ($deliveryChargeByAddress[$address->id] ?? 0);
                                    $isSelectedAddress = (int) $selectedAddressId === (int) $address->id;
                                @endphp
                                <label
                                    class="sf-sidepanel p-3 mb-3 d-block js-checkout-address {{ $loop->index >= 3 && ! $isSelectedAddress ? 'd-none' : '' }}"
                                    data-delivery-charge="{{ $deliveryCharge }}"
                                    data-address-id="{{ $address->id }}"
                                    data-extra-address="{{ $loop->index >= 3 && ! $isSelectedAddress ? 'true' : 'false' }}"
                                >
                                    <div class="d-flex gap-2 align-items-start">
                                        <input
                                            type="radio"
                                            name="address_id"
                                            value="{{ $address->id }}"
                                            class="mt-1"
                                            {{ $isSelectedAddress ? 'checked' : '' }}
                                            required
                                        >
                                        <div>
                                            <div class="fw-semibold">{{ $address->label ?: $address->recipient_name }}</div>
                                            <div class="small text-secondary">{{ $address->address_line_1 }}, {{ $address->city?->city_name }}</div>
                                            <div class="small text-secondary">{{ $address->zone?->zone_name ?? '-' }} | {{ $address->postcode }}</div>
                                            <div class="small text-secondary mt-1">Delivery fee: {{ \App\Support\StoreCurrency::format($deliveryCharge, 0) }}</div>
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <div class="sf-empty-state mb-3">No saved address yet. Add one from Address.</div>
                            @endforelse
                            @if ($addresses->count() > 3)
                                <button type="button" class="btn btn-light rounded-pill px-4 mb-3 js-toggle-checkout-addresses" data-expanded="false">
                                    View more addresses
                                </button>
                            @endif
                            <a href="{{ $addressesUrl }}" class="btn btn-outline-dark rounded-pill">Manage Addresses</a>
                        </div>

                        <div class="sf-info-card">
                            <div class="sf-section-header mb-3">
                                <div>
                                    <h4>Payment Method</h4>
                                    <p class="text-secondary mb-0 small">Choose how you want to complete this order.</p>
                                </div>
                            </div>
                            <div class="sf-payment-options">
                                <label class="sf-payment-option">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="cod"
                                        class="sf-payment-radio"
                                        {{ $checkoutPaymentMethod === 'cod' ? 'checked' : '' }}
                                    >
                                    <span class="sf-payment-check" aria-hidden="true"></span>
                                    <div class="sf-payment-copy">
                                        <span>Cash on Delivery</span>
                                    </div>
                                </label>
                                <label class="sf-payment-option">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="online"
                                        class="sf-payment-radio"
                                        {{ $checkoutPaymentMethod === 'online' ? 'checked' : '' }}
                                    >
                                    <span class="sf-payment-check" aria-hidden="true"></span>
                                    <div class="sf-payment-copy">
                                        <span>Online Payment</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="sf-info-card sf-cart-summary">
                            <h4 class="mb-3">Order Summary</h4>
                            <div class="mb-3">
                                <label class="form-label small text-secondary">Coupon Code</label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        name="coupon_code"
                                        class="form-control"
                                        placeholder="Enter coupon"
                                        value="{{ $cartTotals['coupon']['code'] ?? old('coupon_code') }}"
                                        form="couponApplyForm"
                                        {{ ! empty($cartTotals['coupon']) ? 'readonly' : '' }}
                                    >
                                    @if (! empty($cartTotals['coupon']))
                                        <button class="btn btn-outline-secondary" type="submit" form="couponRemoveForm">Remove</button>
                                    @else
                                        <button class="btn btn-outline-secondary" type="submit" form="couponApplyForm">Apply</button>
                                    @endif
                                </div>
                            </div>
                            @foreach ($cartItems as $item)
                                @php
                                    $baseUnit = \App\Support\StoreOfferPricing::cartItemBaseUnit($item);
                                    $offerUnit = \App\Support\StoreOfferPricing::cartItemOfferUnit($item);
                                    $itemSavings = \App\Support\StoreOfferPricing::cartItemSavings($item);
                                    $discountLabel = \App\Support\StoreOfferPricing::discountLabel($item['product'], $baseUnit, $offerUnit);
                                    $hasOfferPrice = $baseUnit > $offerUnit;
                                @endphp
                                <div class="d-flex justify-content-between align-items-start gap-3 small mb-2">
                                    <span>
                                        <span class="d-block">{{ $item['product']->product_name }} x {{ $item['quantity'] }}</span>
                                        <span class="d-block text-secondary">
                                            {{ $hasOfferPrice ? 'Offer price' : 'Price' }}: <span class="fw-semibold text-success">{{ \App\Support\StoreCurrency::format($offerUnit, 0) }}</span>
                                            @if ($hasOfferPrice)
                                                <span class="text-decoration-line-through ms-1">{{ \App\Support\StoreCurrency::format($baseUnit, 0) }}</span>
                                            @endif
                                        </span>
                                        @if ($itemSavings > 0)
                                            <span class="d-block text-success">{{ $discountLabel ?? 'Offer applied' }}. You save {{ \App\Support\StoreCurrency::format($itemSavings, 0) }}.</span>
                                        @endif
                                    </span>
                                    <strong class="text-nowrap">{{ \App\Support\StoreCurrency::format($item['subtotal'], 0) }}</strong>
                                </div>
                            @endforeach
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Item Total</span>
                                <strong data-item-total>{{ \App\Support\StoreCurrency::format($cartTotals['itemTotal'], 0) }}</strong>
                            </div>
                            @if ($offerSavings > 0)
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Offer Savings</span>
                                    <strong>{{ \App\Support\StoreCurrency::format($offerSavings, 0) }}</strong>
                                </div>
                            @endif
                            @if (($cartTotals['tax'] ?? 0) > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax</span>
                                    <strong data-tax-total>{{ \App\Support\StoreCurrency::format($cartTotals['tax'], 0) }}</strong>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee</span>
                                <strong data-delivery-total>{{ \App\Support\StoreCurrency::format($cartTotals['delivery'], 0) }}</strong>
                            </div>
                            @if (($cartTotals['discount'] ?? 0) > 0)
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Discount{{ ! empty($cartTotals['coupon']['code']) ? ' ('.$cartTotals['coupon']['code'].')' : '' }}</span>
                                    <strong data-discount-total>-{{ \App\Support\StoreCurrency::format($cartTotals['discount'], 0) }}</strong>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">To Pay</span>
                                <strong class="fs-5" data-grand-total>{{ \App\Support\StoreCurrency::format($cartTotals['grandTotal'], 0) }}</strong>
                            </div>

                            <button id="checkoutSubmit" class="btn btn-danger w-100 rounded-pill mt-3" type="submit" {{ $addresses->isEmpty() ? 'disabled' : '' }}>
                                Place Order
                            </button>
                        </div>
                    </div>
                </form>
                <form id="couponApplyForm" method="POST" action="{{ route('storefront.coupon.apply') }}" class="d-none">
                    @csrf
                </form>
                <form id="couponRemoveForm" method="POST" action="{{ route('storefront.coupon.remove') }}" class="d-none">
                    @csrf
                </form>
            @endif
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        window.checkoutDeliveryCharges = @json($deliveryChargeByAddress ?? []);
        window.storeCurrency = @json(\App\Support\StoreCurrency::jsConfig());
        const checkoutSubmit = document.getElementById('checkoutSubmit');
        const addressToggle = document.querySelector('.js-toggle-checkout-addresses');

        document.addEventListener('change', (event) => {
            const radio = event.target.closest('input[name="address_id"]');
            const paymentMethod = event.target.closest('input[name="payment_method"]');

            if (!radio) {
                if (paymentMethod && checkoutSubmit) {
                    checkoutSubmit.textContent = paymentMethod.value === 'online' ? 'Pay Now' : 'Place Order';
                }
                return;
            }

            const wrapper = radio.closest('.js-checkout-address');
            const delivery = Number(wrapper?.dataset.deliveryCharge || 0);
            const itemTotal = Number(@json((float) ($cartTotals['itemTotal'] ?? 0)));
            const tax = Number(@json((float) ($cartTotals['tax'] ?? 0)));
            const discount = Number(@json((float) ($cartTotals['discount'] ?? 0)));
            const grandTotal = Math.max(0, itemTotal - discount) + tax + delivery;

            const deliveryNode = document.querySelector('[data-delivery-total]');
            const grandNode = document.querySelector('[data-grand-total]');

            if (deliveryNode) {
                deliveryNode.textContent = `${window.storeCurrency.code} ${delivery.toLocaleString(window.storeCurrency.locale, { maximumFractionDigits: 0 })}`;
            }

            if (grandNode) {
                grandNode.textContent = `${window.storeCurrency.code} ${grandTotal.toLocaleString(window.storeCurrency.locale, { maximumFractionDigits: 0 })}`;
            }
        });

        const activePaymentMethod = document.querySelector('input[name="payment_method"]:checked');
        if (activePaymentMethod && checkoutSubmit) {
            checkoutSubmit.textContent = activePaymentMethod.value === 'online' ? 'Pay Now' : 'Place Order';
        }

        addressToggle?.addEventListener('click', () => {
            const isExpanded = addressToggle.dataset.expanded === 'true';

            document.querySelectorAll('[data-extra-address="true"]').forEach((address) => {
                address.classList.toggle('d-none', isExpanded);
            });

            addressToggle.dataset.expanded = isExpanded ? 'false' : 'true';
            addressToggle.textContent = isExpanded ? 'View more addresses' : 'View fewer addresses';
        });
    </script>
@endpush
