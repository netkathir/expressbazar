@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="section-heading hero-inline">
            <div>
                <h1>Checkout</h1>
                <p>Complete address, delivery slot, and payment details in one simple flow.</p>
            </div>
        </div>

        <div class="checkout-layout">
            <form class="checkout-form">
                <div class="form-card">
                    <h2>Delivery address</h2>
                    <div class="form-grid">
                        <label>Full name<input type="text" placeholder="Enter your name"></label>
                        <label>Mobile number<input type="text" placeholder="10-digit phone"></label>
                        <label class="full">Address<textarea rows="4" placeholder="House no, street, area, city"></textarea></label>
                    </div>
                </div>

                <div class="form-card">
                    <h2>Delivery slot</h2>
                    <div class="slot-row">
                        <span class="filter-chip active">10:00 - 10:20</span>
                        <span class="filter-chip">10:20 - 10:40</span>
                        <span class="filter-chip">11:00 - 11:20</span>
                    </div>
                </div>

                <div class="form-card">
                    <h2>Payment</h2>
                    <div class="slot-row">
                        <span class="filter-chip active">UPI</span>
                        <span class="filter-chip">Card</span>
                        <span class="filter-chip">Cash on delivery</span>
                    </div>
                </div>
            </form>

            <aside class="summary-card">
                <h2>Cart total</h2>
                @foreach ($cartItems as $item)
                    <div class="summary-row">
                        <span>{{ $item['name'] }}</span>
                        <strong>Rs. {{ $item['price'] * $item['qty'] }}</strong>
                    </div>
                @endforeach
                <hr>
                <div class="summary-row"><span>Total</span><strong>Rs. {{ $orderSummary['total'] }}</strong></div>
                <button class="btn btn-primary block" type="button">Place order</button>
            </aside>
        </div>
    </section>
@endsection
