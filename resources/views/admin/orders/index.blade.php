@extends('layouts.admin')

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'admin.orders';
        $isVendorPanel = $isVendorPanel ?? false;
    @endphp
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Order Management</h1>
            </div>
            @unless ($isVendorPanel)
                @canRoute('admin.orders.create')
                    <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">Add Order</a>
                @endcanRoute
            @endunless
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Order number">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select">
                        <option value="">All</option>
                        @foreach (['pending', 'paid', 'failed', 'refunded'] as $status)
                            <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Order Status</label>
                    <select name="order_status" class="form-select">
                        <option value="">All</option>
                        @foreach (['pending', 'accepted', 'processing', 'dispatched', 'delivered', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('order_status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route($routePrefix.'.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Vendor</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Placed</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="fw-semibold">{{ $order->order_number }}</td>
                            <td>{{ $order->customer?->name ?? '-' }}</td>
                            <td>{{ $order->vendor?->vendor_name ?? '-' }}</td>
                            <td>{{ \App\Support\StoreCurrency::format($order->total_amount) }}</td>
                            <td><span class="badge text-bg-light">{{ ucfirst($order->payment_status) }}</span></td>
                            <td><span class="badge text-bg-{{ $order->order_status === 'completed' ? 'success' : 'secondary' }}">{{ ucfirst($order->order_status) }}</span></td>
                            <td>{{ $order->placed_at?->format('M d, Y') ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route($routePrefix.'.show', $order) }}" class="btn btn-sm btn-outline-secondary" aria-label="View order" title="View order">
                                    <i class="ti ti-eye"></i>
                                </a>
                                @unless ($isVendorPanel)
                                    @canRoute('admin.orders.edit')
                                        <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit order" title="Edit order">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    @endcanRoute
                                    @canRoute('admin.orders.destroy', 'DELETE')
                                        <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this order?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete order" title="Delete order">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    @endcanRoute
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-5">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
