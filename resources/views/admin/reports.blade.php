@extends('admin.layout')

@section('content')
    <div class="hero-panel" style="margin-bottom: 16px;">
        <div>
            <h1 style="margin: 0 0 8px;">Reports</h1>
            <p class="admin-muted" style="margin: 0;">Sales performance, payment status, and top product insights.</p>
        </div>
    </div>

    <section class="admin-grid cols-4" style="margin-bottom: 20px;">
        <div class="metric"><div class="label">Orders</div><div class="value">{{ $stats['orders'] }}</div><div class="foot">Total placed</div></div>
        <div class="metric"><div class="label">Revenue</div><div class="value">Rs. {{ number_format($stats['revenue']) }}</div><div class="foot">Gross sales</div></div>
        <div class="metric"><div class="label">Coupons</div><div class="value">{{ $stats['coupons'] }}</div><div class="foot">Available offers</div></div>
        <div class="metric"><div class="label">Low stock</div><div class="value">{{ $stats['lowStock'] }}</div><div class="foot">Reorder alerts</div></div>
    </section>

    <section class="admin-grid cols-2">
        <div class="admin-card">
            <h2 style="margin-top: 0;">Orders by status</h2>
            <table class="admin-table">
                <thead><tr><th>Status</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($ordersByStatus as $row)
                        <tr>
                            <td class="text-capitalize">{{ $row['status'] }}</td>
                            <td>{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="admin-card">
            <h2 style="margin-top: 0;">Top products</h2>
            <table class="admin-table">
                <thead><tr><th>Product</th><th>Price</th><th>Stock</th></tr></thead>
                <tbody>
                    @foreach ($topProducts as $product)
                        <tr>
                            <td>
                                <div style="display:flex; gap: 12px; align-items:center;">
                                    <img class="thumb" src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                                    <div>{{ $product['name'] }}</div>
                                </div>
                            </td>
                            <td>Rs. {{ number_format($product['sale_price'] ?? $product['price']) }}</td>
                            <td>{{ $product['stock'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
