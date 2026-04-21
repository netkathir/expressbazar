@extends('layouts.storefront')

@section('content')
    @php
        $couponCode = $orderSummary['coupon_code'] ?? null;
    @endphp

    <section class="container section-block">
        <div class="section-title">
            <div class="section-copy">
                <span class="eyebrow">Checkout</span>
                <h1>Delivery details</h1>
                <p>Fill in your address and place the order.</p>
            </div>
        </div>

        <div class="checkout-layout">
            <form class="checkout-card checkout-form" method="POST" action="{{ route('checkout.place') }}">
                @csrf
                <div class="field-grid">
                    <label class="field">
                        <span>Full name</span>
                        <input class="input" type="text" name="shipping_name" value="{{ old('shipping_name') }}">
                    </label>
                    <label class="field">
                        <span>Phone</span>
                        <input class="input" type="text" name="shipping_phone" value="{{ old('shipping_phone') }}">
                    </label>
                    <label class="field">
                        <span>Pincode</span>
                        <input class="input" type="text" name="pincode" value="{{ old('pincode') }}">
                    </label>
                    <label class="field full">
                        <span>Address</span>
                        <textarea class="textarea" name="shipping_address">{{ old('shipping_address', $oldAddress ?? '') }}</textarea>
                    </label>
                </div>

                <div class="form-actions">
                    <a class="btn btn-outline" href="{{ route('cart.show') }}">Back to cart</a>
                    <button class="btn btn-primary" type="submit">Place order</button>
                </div>
            </form>

            <aside class="summary-card">
                <h2>Order summary</h2>
                @if ($couponCode)
                    <div class="summary-row">
                        <span>Coupon</span>
                        <strong>{{ $couponCode }}</strong>
                    </div>
                @endif

                @foreach ($cartItems as $item)
                    <div class="summary-row">
                        <span>{{ $item['name'] }} x {{ $item['quantity'] }}</span>
                        <strong>Rs. {{ $item['line_total'] }}</strong>
                    </div>
                @endforeach
                <hr>
                <div class="summary-row"><span>Subtotal</span><strong>Rs. {{ $orderSummary['subtotal'] }}</strong></div>
                <div class="summary-row"><span>Discount</span><strong>-Rs. {{ $orderSummary['discount_total'] }}</strong></div>
                <div class="summary-row total"><span>Total</span><strong>Rs. {{ $orderSummary['grand_total'] }}</strong></div>
            </aside>
        </div>
    </section>
@endsection
