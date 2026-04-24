@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">Home <span>›</span> Checkout</nav>

            @if ($cartItems->isEmpty())
                <div class="sf-empty-state">Your cart is empty. Add products before checkout.</div>
            @else
                <form method="POST" action="{{ route('storefront.checkout.place') }}">
                    @csrf
                    <div class="sf-cart-page">
                        <div class="sf-info-card">
                            <h3 class="mb-3">Delivery Address</h3>
                            @forelse ($addresses as $address)
                                @php($deliveryCharge = (float) ($deliveryChargeByAddress[$address->id] ?? 0))
                                <label class="sf-sidepanel p-3 mb-3 d-block js-checkout-address" data-delivery-charge="{{ $deliveryCharge }}" data-address-id="{{ $address->id }}">
                                    <div class="d-flex gap-2 align-items-start">
                                        <input
                                            type="radio"
                                            name="address_id"
                                            value="{{ $address->id }}"
                                            class="mt-1"
                                            {{ (int) $selectedAddressId === (int) $address->id ? 'checked' : '' }}
                                            required
                                        >
                                        <div>
                                            <div class="fw-semibold">{{ $address->label ?: $address->recipient_name }}</div>
                                            <div class="small text-secondary">{{ $address->address_line_1 }}, {{ $address->city?->city_name }}</div>
                                            <div class="small text-secondary">{{ $address->zone?->zone_name ?? '-' }} | {{ $address->postcode }}</div>
                                            <div class="small text-secondary mt-1">Delivery fee: ₹{{ number_format($deliveryCharge, 0) }}</div>
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <div class="sf-empty-state mb-3">No saved address yet. Add one from My Account.</div>
                            @endforelse
                            <a href="{{ route('storefront.account') }}" class="btn btn-outline-dark rounded-pill">Manage Addresses</a>
                        </div>

                        <div class="sf-info-card">
                            <h4 class="mb-3">Payment Method</h4>
                            <label class="sf-sidepanel p-3 mb-3 d-block">
                                <div class="d-flex gap-2 align-items-start">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="cod"
                                        class="mt-1"
                                        {{ $checkoutPaymentMethod === 'cod' ? 'checked' : '' }}
                                        required
                                    >
                                    <div>
                                        <div class="fw-semibold">Cash on Delivery</div>
                                        <div class="small text-secondary">Pay when the order reaches you.</div>
                                    </div>
                                </div>
                            </label>
                            <label class="sf-sidepanel p-3 mb-0 d-block">
                                <div class="d-flex gap-2 align-items-start">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="online"
                                        class="mt-1"
                                        {{ $checkoutPaymentMethod === 'online' ? 'checked' : '' }}
                                    >
                                    <div>
                                        <div class="fw-semibold">Online Payment</div>
                                        <div class="small text-secondary">Stripe test checkout will open after you place the order.</div>
                                    </div>
                                </div>
                            </label>
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
                            <div class="d-flex justify-content-between mb-2">
                                <span>Item Total</span>
                                <strong data-item-total>{{ number_format($cartTotals['itemTotal'], 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee</span>
                                <strong data-delivery-total>{{ number_format($cartTotals['delivery'], 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">To Pay</span>
                                <strong class="fs-5" data-grand-total>₹{{ number_format($cartTotals['grandTotal'], 0) }}</strong>
                            </div>

                            <button id="checkoutSubmit" class="btn btn-danger w-100 rounded-pill mt-3" type="submit" {{ $addresses->isEmpty() ? 'disabled' : '' }}>
                                Place Order
                            </button>
                            <div class="text-secondary small mt-2">
                                Delivery is validated against the selected address before the order is created.
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        window.checkoutDeliveryCharges = @json($deliveryChargeByAddress ?? []);
        const checkoutSubmit = document.getElementById('checkoutSubmit');

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
            const grandTotal = itemTotal + delivery;

            const deliveryNode = document.querySelector('[data-delivery-total]');
            const grandNode = document.querySelector('[data-grand-total]');

            if (deliveryNode) {
                deliveryNode.textContent = delivery.toLocaleString('en-IN', { maximumFractionDigits: 0 });
            }

            if (grandNode) {
                grandNode.textContent = `₹${grandTotal.toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;
            }
        });

        const activePaymentMethod = document.querySelector('input[name="payment_method"]:checked');
        if (activePaymentMethod && checkoutSubmit) {
            checkoutSubmit.textContent = activePaymentMethod.value === 'online' ? 'Pay Now' : 'Place Order';
        }
    </script>
@endpush
