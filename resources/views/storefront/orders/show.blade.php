@extends('layouts.storefront')

@section('content')
    @php($latestPayment = $order->payments->last())
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">Home <span>›</span> My Orders <span>›</span> {{ $order->order_number }}</nav>

            <div class="sf-info-card mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="badge rounded-pill text-bg-light mb-2">Order Details</span>
                        <h1 class="h3 fw-bold mb-2">{{ $order->order_number }}</h1>
                        <div class="text-secondary">Placed on {{ optional($order->placed_at)->format('d M Y, h:i A') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold fs-4">₹{{ number_format((float) $order->total_amount, 0) }}</div>
                        <span class="badge rounded-pill text-bg-light">{{ ucfirst($order->order_status) }}</span>
                        <span class="badge rounded-pill text-bg-warning">{{ ucfirst($latestPayment?->status ?? $order->payment_status) }}</span>
                    </div>
                </div>
            </div>

            <div class="sf-detail-grid">
                <div class="sf-info-card">
                    <h4 class="mb-3">Order Items</h4>
                    <div class="d-grid gap-3">
                        @foreach ($order->items as $item)
                            <div class="sf-sidepanel p-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $item->item_name }}</div>
                                        <div class="small text-secondary">{{ $item->quantity }} × ₹{{ number_format((float) $item->price, 0) }}</div>
                                    </div>
                                    <div class="fw-semibold">₹{{ number_format((float) $item->subtotal, 0) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="sf-info-card">
                    <h4 class="mb-3">Summary</h4>
                    <dl class="sf-specs mb-0">
                        <dt>Vendor</dt><dd>{{ $order->vendor?->vendor_name ?? '-' }}</dd>
                        <dt>Payment Method</dt><dd>{{ ucfirst($latestPayment?->payment_method ?? 'cod') }}</dd>
                        <dt>Payment Status</dt><dd>{{ ucfirst($latestPayment?->status ?? $order->payment_status) }}</dd>
                        <dt>Delivery Charge</dt><dd>₹{{ number_format((float) $order->delivery_charge, 0) }}</dd>
                        <dt>Order Status</dt><dd>{{ ucfirst($order->order_status) }}</dd>
                    </dl>

                    @if (($latestPayment?->payment_method ?? null) === 'online')
                        <form method="POST" action="{{ route('storefront.orders.retry-payment', $order) }}" class="mt-4">
                            @csrf
                            <button class="btn btn-danger w-100 rounded-pill">Retry Payment</button>
                            <div class="small text-secondary mt-2">A new payment attempt will be created for this order.</div>
                        </form>
                    @endif

                    <a href="{{ route('storefront.account') }}" class="btn btn-outline-dark w-100 rounded-pill mt-3">Back to Account</a>
                </div>
            </div>
        </section>
    </main>
@endsection
