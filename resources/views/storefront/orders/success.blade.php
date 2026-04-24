@extends('layouts.storefront')

@section('content')
    @php($latestPayment = $order->payments->last())
    @php($paymentStatus = strtolower((string) ($latestPayment?->status ?? $order->payment_status)))
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">Home <span>&rsaquo;</span> Checkout <span>&rsaquo;</span> Success</nav>

            <div class="sf-info-card text-center py-5">
                <div class="mb-3">
                    <span class="badge rounded-pill {{ $paymentStatus === 'paid' ? 'text-bg-success' : 'text-bg-warning' }} px-3 py-2">
                        {{ $paymentStatus === 'paid' ? 'Payment Confirmed' : 'Order Placed' }}
                    </span>
                </div>
                <h1 class="h2 fw-bold mb-2">
                    {{ $paymentStatus === 'paid' ? 'Your Stripe payment is complete' : 'Your order has been placed successfully' }}
                </h1>
                <p class="text-secondary mb-4">
                    Order number <strong>{{ $order->order_number }}</strong>
                    {{ $paymentStatus === 'paid' ? 'has been confirmed and is ready for processing.' : 'is now pending processing.' }}
                </p>

                <div class="sf-detail-grid mb-4">
                    <div class="sf-info-card text-start">
                        <h4 class="mb-3">Order Summary</h4>
                        <dl class="sf-specs mb-0">
                            <dt>Total Amount</dt><dd>&#8377;{{ number_format((float) $order->total_amount, 0) }}</dd>
                            <dt>Payment Status</dt><dd>{{ ucfirst($latestPayment?->status ?? $order->payment_status) }}</dd>
                            <dt>Payment Method</dt><dd>{{ ucfirst($latestPayment?->payment_method ?? 'cod') }}</dd>
                            <dt>Delivery Charge</dt><dd>&#8377;{{ number_format((float) $order->delivery_charge, 0) }}</dd>
                            <dt>Vendor</dt><dd>{{ $order->vendor?->vendor_name ?? '-' }}</dd>
                        </dl>
                    </div>
                    <div class="sf-info-card text-start">
                        <h4 class="mb-3">Next Steps</h4>
                        <div class="d-grid gap-2">
                            <div class="sf-coupon-row"><span>Track this order from your order history</span><i class="ti ti-chevron-right"></i></div>
                            <div class="sf-coupon-row"><span>{{ $paymentStatus === 'paid' ? 'Payment confirmed through Stripe' : 'Retry payment later if needed' }}</span><i class="ti ti-chevron-right"></i></div>
                            <div class="sf-coupon-row"><span>Continue shopping for more items</span><i class="ti ti-chevron-right"></i></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('storefront.orders.show', $order) }}" class="btn btn-dark rounded-pill px-4">View Order Details</a>
                    <a href="{{ route('storefront.orders.index') }}" class="btn btn-outline-dark rounded-pill px-4">My Orders</a>
                    <a href="{{ route('user.home') }}" class="btn btn-danger rounded-pill px-4">Continue Shopping</a>
                </div>
            </div>
        </section>
    </main>
@endsection
