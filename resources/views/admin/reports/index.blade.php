@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <h1 class="h3 mb-1">Reports & Analytics</h1>
            <p class="text-secondary mb-0">Quick operational snapshot from live data.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shell-card"><div class="card-body p-4"><div class="text-secondary small">Orders</div><div class="h3 mb-0">{{ $summary['orders'] }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card shell-card"><div class="card-body p-4"><div class="text-secondary small">Revenue</div><div class="h3 mb-0">{{ number_format((float) $summary['revenue'], 2) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card shell-card"><div class="card-body p-4"><div class="text-secondary small">Active Vendors</div><div class="h3 mb-0">{{ $summary['active_vendors'] }} / {{ $summary['vendors'] }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card shell-card"><div class="card-body p-4"><div class="text-secondary small">Low Stock Items</div><div class="h3 mb-0">{{ $summary['low_stock'] }}</div></div></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shell-card">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Recent Orders</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Vendor</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentOrders as $order)
                                    <tr>
                                        <td>{{ $order->order_number }}</td>
                                        <td>{{ $order->customer?->name ?? '-' }}</td>
                                        <td>{{ $order->vendor?->vendor_name ?? '-' }}</td>
                                        <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-secondary py-4">No orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shell-card mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Recent Payments</h2>
                    @forelse ($recentPayments as $payment)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold">{{ $payment->transaction_id }}</div>
                            <div class="small text-secondary">{{ $payment->order?->order_number ?? '-' }} - {{ ucfirst($payment->status) }}</div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No payment records yet.</p>
                    @endforelse
                </div>
            </div>
            <div class="card shell-card">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Low Stock Items</h2>
                    @forelse ($lowStockItems as $inventory)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold">{{ $inventory->product?->product_name ?? '-' }}</div>
                            <div class="small text-secondary">{{ $inventory->stock_quantity }} remaining</div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No low stock alerts.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
