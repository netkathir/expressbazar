@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Orders</h2>
            <p>Track placed orders and their current status.</p>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Vendor</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong></td>
                            <td>
                                <div>{{ $order->shipping_name ?? 'Customer' }}</div>
                                <div class="admin-muted">{{ $order->shipping_phone ?? 'No phone' }}</div>
                            </td>
                            <td>{{ $order->vendor?->name ?? 'Vendor' }}</td>
                            <td>{{ $order->items->count() }}</td>
                            <td>Rs. {{ number_format((float) $order->grand_total) }}</td>
                            <td><span class="admin-badge">{{ ucfirst($order->status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-muted">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links('admin.partials.pagination') }}
    </section>
@endsection
