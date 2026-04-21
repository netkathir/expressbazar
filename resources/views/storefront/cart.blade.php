@extends('layouts.storefront')

@section('content')
    @php
        $couponCode = $orderSummary['coupon_code'] ?? null;
    @endphp

    <section class="container section-block">
        <div class="section-title">
            <div class="section-copy">
                <span class="eyebrow">Cart</span>
                <h1>Your cart</h1>
                <p>One vendor at a time. Review items, apply coupon, and continue to checkout.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('checkout.show') }}">Checkout</a>
        </div>

        @if ($cartItems === [])
            <div class="empty-state">
                <h2>Your cart is empty</h2>
                <p>Go back and choose a location, vendor, and product.</p>
            </div>
        @else
            <div class="cart-layout">
                <div class="cart-list">
                    @foreach ($cartItems as $item)
                        <article class="cart-item">
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}">
                            <div class="cart-copy">
                                <h3>{{ $item['name'] }}</h3>
                                <p>{{ $item['vendor_name'] }}</p>
                                <div class="price-row" style="margin-top: 10px;">
                                    <span class="price">Rs. {{ $item['unit_price'] }}</span>
                                    <span class="muted">Qty {{ $item['quantity'] }}</span>
                                </div>
                                <div class="save-text">Line total: Rs. {{ $item['line_total'] }}</div>
                                <div class="cart-actions">
                                    <form method="POST" action="{{ route('cart.remove', $item['vendor_product_id']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline" type="submit">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="summary-card">
                    <h2>Summary</h2>

                    <form class="coupon-box" method="POST" action="{{ route('cart.coupon.apply') }}">
                        @csrf
                        <label class="field">
                            <span>Coupon code</span>
                            <div class="coupon-inline">
                                <input class="input" type="text" name="coupon_code" value="{{ old('coupon_code', $couponCode) }}" placeholder="WELCOME10">
                                <button class="btn btn-primary" type="submit">Apply</button>
                            </div>
                        </label>
                        @error('coupon_code')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </form>

                    @if ($couponCode)
                        <div class="summary-row">
                            <span>Applied coupon</span>
                            <strong>{{ $couponCode }}</strong>
                        </div>
                        <form method="POST" action="{{ route('cart.coupon.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline btn-block" type="submit">Remove coupon</button>
                        </form>
                    @endif

                    <hr>
                    <div class="summary-row"><span>Subtotal</span><strong>Rs. {{ $orderSummary['subtotal'] }}</strong></div>
                    <div class="summary-row"><span>Discount</span><strong>-Rs. {{ $orderSummary['discount_total'] }}</strong></div>
                    <div class="summary-row total"><span>Total</span><strong>Rs. {{ $orderSummary['grand_total'] }}</strong></div>
                    <hr>
                    <form method="POST" action="{{ route('cart.clear') }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline btn-block" type="submit">Clear cart</button>
                    </form>
                    <div style="height: 10px;"></div>
                    <a class="btn btn-primary btn-block" href="{{ route('checkout.show') }}">Proceed to checkout</a>
                </aside>
            </div>
        @endif
    </section>
@endsection
