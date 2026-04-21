@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="section-heading hero-inline">
            <div>
                <h1>Your cart</h1>
                <p>Review items, quantities, and savings before moving to checkout.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('checkout.show') }}">Proceed to checkout</a>
        </div>

        <div class="cart-layout">
            <div class="cart-list">
                @foreach ($cartItems as $item)
                    <article class="cart-item">
                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}">
                        <div class="cart-copy">
                            <h3>{{ $item['name'] }}</h3>
                            <p>{{ $item['unit'] }}</p>
                            <div class="price-row">
                                <span class="price">Rs. {{ $item['price'] }}</span>
                                <span class="qty">Qty {{ $item['qty'] }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="summary-card">
                <h2>Order summary</h2>
                <div class="summary-row"><span>Subtotal</span><strong>Rs. {{ $orderSummary['subtotal'] }}</strong></div>
                <div class="summary-row"><span>Delivery fee</span><strong>Rs. {{ $orderSummary['deliveryFee'] }}</strong></div>
                <div class="summary-row"><span>Handling fee</span><strong>Rs. {{ $orderSummary['handlingFee'] }}</strong></div>
                <div class="summary-row"><span>Discount</span><strong>-Rs. {{ $orderSummary['discount'] }}</strong></div>
                <div class="summary-row total"><span>Total</span><strong>Rs. {{ $orderSummary['total'] }}</strong></div>
                <a class="btn btn-primary block" href="{{ route('checkout.show') }}">Continue</a>
            </aside>
        </div>
    </section>
@endsection
