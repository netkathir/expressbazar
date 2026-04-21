@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="success-layout">
            <div class="success-card success-hero">
                <span class="eyebrow">Order placed</span>
                <h1 style="margin-top: 16px;">Thanks for shopping with ExpressBazar</h1>
                <p>Your order has been placed successfully and is ready for processing.</p>

                <div class="success-meta">
                    <div><strong>Order number:</strong> {{ $order->order_number }}</div>
                    <div><strong>Status:</strong> {{ ucfirst($order->status) }}</div>
                    <div><strong>Total:</strong> Rs. {{ $order->grand_total }}</div>
                </div>

                <div class="form-actions" style="margin-top: 18px;">
                    <a class="btn btn-secondary" href="{{ route('home') }}">Continue shopping</a>
                    @auth
                        <a class="btn btn-primary" href="{{ route('orders.mine') }}">My orders</a>
                    @endauth
                </div>
            </div>

            <aside class="summary-card">
                <h2>Order details</h2>
                <div class="summary-row"><span>Name</span><strong>{{ $order->shipping_name }}</strong></div>
                <div class="summary-row"><span>Phone</span><strong>{{ $order->shipping_phone }}</strong></div>
                <div class="summary-row"><span>Address</span><strong>{{ $order->shipping_address }}</strong></div>
                <hr>

                <div class="summary-row"><span>Subtotal</span><strong>Rs. {{ $order->subtotal }}</strong></div>
                <div class="summary-row"><span>Discounts</span><strong>-Rs. {{ $order->discount_total }}</strong></div>
                <div class="summary-row total"><span>Grand total</span><strong>Rs. {{ $order->grand_total }}</strong></div>

                <div class="order-list">
                    @foreach ($order->items as $item)
                        <article class="order-card">
                            <h3>{{ $item->product?->name ?? 'Product' }}</h3>
                            <p>Qty {{ $item->quantity }} | Rs. {{ $item->line_total }}</p>
                        </article>
                    @endforeach
                </div>
            </aside>
        </div>
    </section>
@endsection
