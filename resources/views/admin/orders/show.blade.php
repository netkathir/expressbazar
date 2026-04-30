@extends('layouts.admin')

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'admin.orders';
        $isVendorPanel = $isVendorPanel ?? false;
        $panelUser = $isVendorPanel ? auth('vendor')->user() : auth()->user();
        $canUpdateOrders = $isVendorPanel
            ? $panelUser?->hasRolePermission('orders', 'edit')
            : ($panelUser?->hasRolePermission('orders', 'edit') ?? true);
    @endphp
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Order {{ $order->order_number }}</h1>
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
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shell-card">
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Customer</dt>
                        <dd class="col-sm-8">{{ $order->customer?->name ?? '-' }}</dd>
                        <dt class="col-sm-4">Vendor</dt>
                        <dd class="col-sm-8">{{ $order->vendor?->vendor_name ?? '-' }}</dd>
                        <dt class="col-sm-4">Total Amount</dt>
                        <dd class="col-sm-8">{{ number_format((float) $order->total_amount, 2) }}</dd>
                        <dt class="col-sm-4">Payment Status</dt>
                        <dd class="col-sm-8">{{ ucfirst($order->payment_status) }}</dd>
                        <dt class="col-sm-4">Order Status</dt>
                        <dd class="col-sm-8">{{ ucfirst($order->order_status) }}</dd>
                        <dt class="col-sm-4">Placed At</dt>
                        <dd class="col-sm-8">{{ $order->placed_at?->format('M d, Y h:i A') ?? '-' }}</dd>
                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8">{{ $order->notes ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shell-card">
                <div class="card-body p-4">
                    <h2 class="h5">Order Items</h2>
                    @forelse ($order->items as $item)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold">{{ $item->item_name }}</div>
                            <div class="text-secondary small">{{ $item->quantity }} x {{ number_format((float) $item->price, 2) }}</div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No items recorded for this order.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
