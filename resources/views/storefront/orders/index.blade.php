@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">Home <span>›</span> My Orders</nav>

            <div class="sf-info-card mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <span class="badge rounded-pill text-bg-light mb-2">Order History</span>
                        <h1 class="h3 fw-bold mb-1">My Orders</h1>
                        <div class="text-secondary">View and manage your recent orders.</div>
                    </div>
                    <a href="{{ route('user.home') }}" class="btn btn-outline-dark rounded-pill">Continue Shopping</a>
                </div>
            </div>

            <div class="d-grid gap-3">
                @forelse ($orders as $order)
                    @php($latestPayment = $order->payments->last())
                    @php($orderStatus = mb_strtolower((string) $order->order_status))
                    @php($displayPaymentStatus = $orderStatus === 'cancelled' ? 'cancelled' : ($latestPayment?->status ?? $order->payment_status))
                    <div class="sf-info-card">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold fs-5">{{ $order->order_number }}</div>
                                <div class="text-secondary small">{{ $order->vendor?->vendor_name ?? 'Store order' }}</div>
                                <div class="text-secondary small">Placed on {{ optional($order->placed_at)->format('d M Y, h:i A') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold fs-4">₹{{ number_format((float) $order->total_amount, 0) }}</div>
                                <span class="badge rounded-pill text-bg-{{ $displayPaymentStatus === 'paid' ? 'success' : ($displayPaymentStatus === 'cancelled' ? 'secondary' : 'warning') }}">
                                    {{ ucfirst($displayPaymentStatus) }}
                                </span>
                                <div class="small text-secondary mt-1">Order status: {{ ucfirst($order->order_status) }}</div>
                                <div class="mt-3 d-flex flex-wrap justify-content-end gap-2">
                                    <a href="{{ route('storefront.orders.show', $order) }}" class="btn btn-outline-dark btn-sm rounded-pill">View Details</a>
                                    @if (!in_array($orderStatus, ['dispatched', 'delivered', 'completed', 'cancelled'], true))
                                        <form method="POST" action="{{ route('storefront.orders.cancel-order', $order) }}" onsubmit="return confirm('Cancel this order?');">
                                            @csrf
                                            <button class="btn btn-outline-dark btn-sm rounded-pill">Cancel Order</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('storefront.orders.reorder', $order) }}">
                                        @csrf
                                        <button class="btn btn-outline-dark btn-sm rounded-pill">Reorder</button>
                                    </form>
                                    @if ($orderStatus !== 'cancelled' && ($latestPayment?->payment_method ?? null) === 'online' && in_array($latestPayment?->status, ['pending', 'failed'], true))
                                        <form method="POST" action="{{ route('storefront.orders.retry-payment', $order) }}">
                                            @csrf
                                            <button class="btn btn-danger btn-sm rounded-pill">Pay with Stripe</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-secondary small">
                            {{ $order->items->count() }} item(s) • Delivery ₹{{ number_format((float) $order->delivery_charge, 0) }}
                        </div>
                    </div>
                @empty
                    <x-empty-state>{{ config('ui_messages.no_orders') }}</x-empty-state>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </section>
    </main>
@endsection
