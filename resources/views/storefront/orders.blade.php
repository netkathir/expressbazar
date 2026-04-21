@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="section-title">
            <div class="section-copy">
                <span class="eyebrow">Account</span>
                <h1>My Orders</h1>
                <p>Your recent checkout history and order statuses.</p>
            </div>
            <div class="form-actions">
                <a class="btn btn-outline" href="{{ route('home') }}">Continue shopping</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Logout</button>
                </form>
            </div>
        </div>

        @if (($orders ?? collect())->isEmpty())
            <div class="empty-state">
                <h2>No orders yet</h2>
                <p>Start shopping to see your orders here.</p>
            </div>
        @else
            <div class="order-grid">
                @foreach ($orders as $order)
                    <article class="order-card">
                        <div class="order-card-head">
                            <div>
                                <strong>{{ $order->order_number }}</strong>
                                <p>{{ $order->created_at?->format('d M Y, h:i A') }}</p>
                            </div>
                            <span class="status-pill">{{ ucfirst($order->status) }}</span>
                        </div>

                        <div class="summary-row"><span>Vendor</span><strong>{{ $order->vendor?->name ?? 'ExpressBazar' }}</strong></div>
                        <div class="summary-row"><span>Items</span><strong>{{ $order->items->count() }}</strong></div>
                        <div class="summary-row"><span>Subtotal</span><strong>Rs. {{ $order->subtotal }}</strong></div>
                        <div class="summary-row"><span>Discount</span><strong>-Rs. {{ $order->discount_total }}</strong></div>
                        <div class="summary-row total"><span>Grand total</span><strong>Rs. {{ $order->grand_total }}</strong></div>

                        <div class="order-items-mini">
                            @foreach ($order->items->take(3) as $item)
                                <div class="order-item-mini">
                                    <span>{{ $item->product?->name ?? 'Product' }}</span>
                                    <strong>x{{ $item->quantity }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
