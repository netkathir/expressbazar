@extends('layouts.storefront')

@section('content')
    @php($latestPayment = $order->payments->last())
    @php($orderStatus = mb_strtolower((string) $order->order_status))
    @php($displayPaymentStatus = $orderStatus === 'cancelled' ? 'cancelled' : ($latestPayment?->status ?? $order->payment_status))
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">Home <span>&rsaquo;</span> My Orders <span>&rsaquo;</span> {{ $order->order_number }}</nav>

            <div class="sf-info-card mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="badge rounded-pill text-bg-light mb-2">Order Details</span>
                        <h1 class="h3 fw-bold mb-2">{{ $order->order_number }}</h1>
                        <div class="text-secondary">Placed on {{ optional($order->placed_at)->format('d M Y, h:i A') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold fs-4">&#8377;{{ number_format((float) $order->total_amount, 0) }}</div>
                        <span class="badge rounded-pill text-bg-{{ $displayPaymentStatus === 'paid' ? 'success' : ($displayPaymentStatus === 'cancelled' ? 'secondary' : 'warning') }}">
                            {{ ucfirst($displayPaymentStatus) }}
                        </span>
                        <div class="small text-secondary mt-1">Order status: <span id="order-status">{{ ucfirst($order->order_status) }}</span></div>
                    </div>
                </div>
            </div>

            <div class="sf-detail-grid">
                <div class="sf-info-card">
                    <h4 class="mb-3">Order Items</h4>
                    <div class="d-grid gap-3">
                        @foreach ($order->items as $item)
                            <div class="sf-sidepanel p-3">
                                <div class="d-flex justify-content-between gap-3 align-items-center">
                                    <div class="d-flex gap-3 align-items-center">
                                        @if ($item->product)
                                            <a href="{{ route('storefront.product', $item->product) }}" class="flex-shrink-0">
                                                <img src="{{ $item->product->images->first() ? asset($item->product->images->first()->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $item->item_name }}" style="width: 56px; height: 56px; object-fit: cover; border-radius: 10px;">
                                            </a>
                                        @endif
                                        <div>
                                            <a href="{{ $item->product ? route('storefront.product', $item->product) : '#' }}" class="fw-semibold text-decoration-none text-dark">{{ $item->item_name }}</a>
                                            <div class="small text-secondary">{{ $item->quantity }} &times; &#8377;{{ number_format((float) $item->price, 0) }}</div>
                                        </div>
                                    </div>
                                    <div class="fw-semibold">&#8377;{{ number_format((float) $item->subtotal, 0) }}</div>
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
                        <dt>Payment Status</dt><dd>{{ ucfirst($displayPaymentStatus) }}</dd>
                        <dt>Delivery Charge</dt><dd>&#8377;{{ number_format((float) $order->delivery_charge, 0) }}</dd>
                        <dt>Order Status</dt><dd id="order-status-summary">{{ ucfirst($order->order_status) }}</dd>
                    </dl>

                    @if ($orderStatus !== 'cancelled' && ($latestPayment?->payment_method ?? null) === 'online' && in_array($latestPayment?->status, ['pending', 'failed'], true))
                        <form method="POST" action="{{ route('storefront.orders.retry-payment', $order) }}" class="mt-4">
                            @csrf
                            <button class="btn btn-danger w-100 rounded-pill">Pay with Stripe</button>
                            <div class="small text-secondary mt-2">A new Stripe checkout session will be created for this order.</div>
                        </form>
                    @endif

                    @if (!in_array($orderStatus, ['dispatched', 'delivered', 'completed', 'cancelled'], true))
                        <form method="POST" action="{{ route('storefront.orders.cancel-order', $order) }}" class="mt-3" onsubmit="return confirm('Cancel this order?');">
                            @csrf
                            <button class="btn btn-outline-dark w-100 rounded-pill">Cancel Order</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('storefront.orders.reorder', $order) }}" class="mt-3">
                        @csrf
                        <button class="btn btn-outline-dark w-100 rounded-pill">Reorder</button>
                    </form>

                    <a href="{{ route('storefront.account') }}" class="btn btn-outline-dark w-100 rounded-pill mt-3">Back to Account</a>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        (() => {
            const statusUrl = @json(route('storefront.orders.status', $order));
            const statusEls = [
                document.getElementById('order-status'),
                document.getElementById('order-status-summary'),
            ].filter(Boolean);

            if (!statusEls.length) {
                return;
            }

            const refreshStatus = async () => {
                const response = await fetch(statusUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const payload = await response.json();

                if (payload.label) {
                    statusEls.forEach((el) => {
                        el.textContent = payload.label;
                    });
                }
            };

            window.setInterval(() => {
                refreshStatus().catch(() => {});
            }, 10000);
        })();
    </script>
@endpush
