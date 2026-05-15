@extends('layouts.admin')

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'admin.orders';
        $isVendorPanel = $isVendorPanel ?? false;
        $panelUser = $isVendorPanel ? auth('vendor')->user() : auth()->user();
        $canUpdateOrders = $isVendorPanel
            ? $panelUser?->hasRolePermission('orders', 'edit')
            : ($panelUser?->hasRolePermission('orders', 'edit') ?? true);
        $paymentStatusClass = match ($order->payment_status) {
            'paid' => 'success',
            'failed' => 'danger',
            'refunded' => 'warning',
            default => 'secondary',
        };
        $orderStatusClass = match ($order->order_status) {
            'delivered', 'completed' => 'success',
            'cancelled' => 'danger',
            'accepted', 'processing', 'dispatched' => 'primary',
            default => 'secondary',
        };
    @endphp
    <div class="order-detail-page">
        <div class="card shell-card order-detail-hero mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="order-detail-title">
                        <span class="order-detail-kicker">{{ $isVendorPanel ? 'Vendor order' : 'Order details' }}</span>
                        <h1 class="h3 mb-2">Order {{ $order->order_number }}</h1>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge text-bg-{{ $paymentStatusClass }}">Payment {{ ucfirst($order->payment_status) }}</span>
                            <span class="badge text-bg-{{ $orderStatusClass }}">Order {{ ucfirst($order->order_status) }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($isVendorPanel && $canUpdateOrders)
                            @if ($order->order_status === 'pending')
                                <form method="POST" action="{{ route('vendor.orders.accept', $order) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Accept</button>
                                </form>
                                <form method="POST" action="{{ route('vendor.orders.reject', $order) }}" onsubmit="return confirm('Reject this order?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger">Reject</button>
                                </form>
                            @elseif ($order->order_status === 'accepted')
                                <form method="POST" action="{{ route('vendor.orders.processing', $order) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Processing</button>
                                </form>
                            @elseif ($order->order_status === 'processing')
                                <form method="POST" action="{{ route('vendor.orders.dispatched', $order) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Dispatch</button>
                                </form>
                            @elseif ($order->order_status === 'dispatched')
                                <form method="POST" action="{{ route('vendor.orders.delivered', $order) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Deliver</button>
                                </form>
                            @endif
                        @endif
                        <a href="{{ route($routePrefix.'.index') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </div>

                <div class="order-detail-metrics mt-4">
                    <div>
                        <span>Customer</span>
                        <strong>{{ $order->customer?->name ?? '-' }}</strong>
                    </div>
                    <div>
                        <span>Vendor</span>
                        <strong>{{ $order->vendor?->vendor_name ?? '-' }}</strong>
                    </div>
                    <div>
                        <span>Placed At</span>
                        <strong>{{ $order->placed_at?->format('M d, Y h:i A') ?? '-' }}</strong>
                    </div>
                    <div>
                        <span>Total Amount</span>
                        <strong>{{ \App\Support\StoreCurrency::format($order->total_amount) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card shell-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h2 class="h5 mb-0">Product Details</h2>
                            <span class="badge text-bg-light">{{ $order->items->count() }} item(s)</span>
                        </div>

                        <div class="order-product-list">
                            @forelse ($order->items as $item)
                                @php
                                    $product = $item->product;
                                    $imagePath = $product?->images?->first()?->image_path;
                                    $lineTotal = ! is_null($item->subtotal) ? (float) $item->subtotal : ((float) $item->price * (int) $item->quantity);
                                @endphp
                                <div class="order-product-card">
                                    <div class="order-product-media">
                                        @if ($imagePath)
                                            <img src="{{ asset($imagePath) }}" alt="{{ $product?->product_name ?? $item->item_name }}">
                                        @else
                                            <i class="ti ti-package"></i>
                                        @endif
                                    </div>
                                    <div class="order-product-copy">
                                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                            <div>
                                                <h3>{{ $product?->product_name ?? $item->item_name }}</h3>
                                                <div class="order-product-meta">
                                                    <span>{{ $product?->category?->category_name ?? 'Order item' }}</span>
                                                    @if ($product?->unit)
                                                        <span>{{ $product->unit }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <strong>{{ \App\Support\StoreCurrency::format($lineTotal) }}</strong>
                                        </div>
                                        @if ($product?->description)
                                            <p>{{ $product->description }}</p>
                                        @endif
                                        <div class="order-product-pricing">
                                            <span>Qty: {{ (int) $item->quantity }}</span>
                                            <span>Unit price: {{ \App\Support\StoreCurrency::format($item->price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-secondary py-3">No items recorded for this order.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card shell-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Order Summary</h2>
                        <div class="order-summary-list">
                            <div><span>Subtotal</span><strong>{{ \App\Support\StoreCurrency::format(max(0, (float) $order->total_amount - (float) $order->delivery_charge)) }}</strong></div>
                            <div><span>Delivery Charge</span><strong>{{ \App\Support\StoreCurrency::format($order->delivery_charge) }}</strong></div>
                            <div class="order-summary-total"><span>Total</span><strong>{{ \App\Support\StoreCurrency::format($order->total_amount) }}</strong></div>
                        </div>
                    </div>
                </div>

                <div class="card shell-card">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Additional Details</h2>
                        <dl class="order-detail-list mb-0">
                            <dt>Payment Status</dt>
                            <dd>{{ ucfirst($order->payment_status) }}</dd>
                            <dt>Order Status</dt>
                            <dd>{{ ucfirst($order->order_status) }}</dd>
                            <dt>Notes</dt>
                            <dd>{{ $order->notes ?: '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
