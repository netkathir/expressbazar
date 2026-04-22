@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Order {{ $order->order_number }}</h1>
                <p class="text-secondary mb-0">Review order details and status history.</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Back</a>
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
