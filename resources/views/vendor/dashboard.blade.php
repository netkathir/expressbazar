@extends('layouts.admin')

@section('content')
    <div class="card hero-card mb-4">
        <div class="card-body p-4 p-md-5 d-flex flex-wrap justify-content-between gap-4">
            <div>
                <h1 class="h2 mb-2">Vendor Dashboard</h1>
                <p class="text-secondary mb-0">{{ $showSetupHint ? 'Complete setup and add your first products to start receiving orders.' : 'Manage your catalog and respond to new orders.' }}</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @if ($canViewProducts)
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="text-secondary small">Products</div>
                    <div class="h3 mb-0">{{ $productCount }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="text-secondary small">Low Stock</div>
                    <div class="h3 mb-0">{{ $lowStockCount }}</div>
                </div>
            </div>
        @endif
        @if ($canViewOrders)
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="text-secondary small">Total Orders</div>
                    <div class="h3 mb-0">{{ $totalOrderCount }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="text-secondary small">Pending Orders</div>
                    <div class="h3 mb-0">{{ $pendingOrderCount }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="text-secondary small">Active Orders</div>
                    <div class="h3 mb-0">{{ $activeOrderCount }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="text-secondary small">Delivered Revenue</div>
                    <div class="h3 mb-0">{{ number_format((float) $deliveredRevenue, 2) }}</div>
                </div>
            </div>
        @endif
    </div>

    @if ($canViewOrders)
        <div class="card shell-card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Recent Orders</h2>
                    <a href="{{ route('vendor.orders.index') }}" class="btn btn-outline-secondary btn-sm">View Orders</a>
                </div>
                @forelse ($recentOrders as $order)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <div class="fw-semibold">{{ $order->order_number }}</div>
                            <div class="small text-secondary">{{ $order->customer?->name ?? '-' }}</div>
                        </div>
                        <span class="badge text-bg-secondary">{{ ucfirst($order->order_status) }}</span>
                    </div>
                @empty
                    <x-empty-state>No orders found.</x-empty-state>
                @endforelse
            </div>
        </div>
    @endif
@endsection
