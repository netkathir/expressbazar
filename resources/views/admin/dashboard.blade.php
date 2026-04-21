@extends('admin.layout')

@section('content')
    <section class="admin-summary-grid">
        <article class="admin-stat-card">
            <div class="label">Locations</div>
            <div class="value">{{ $stats['locations'] }}</div>
            <div class="meta">Mapped delivery pincodes</div>
        </article>
        <article class="admin-stat-card">
            <div class="label">Categories</div>
            <div class="value">{{ $stats['categories'] }}</div>
            <div class="meta">Catalog root groups</div>
        </article>
        <article class="admin-stat-card">
            <div class="label">Subcategories</div>
            <div class="value">{{ $stats['subcategories'] }}</div>
            <div class="meta">Browsing sections</div>
        </article>
        <article class="admin-stat-card">
            <div class="label">Vendors</div>
            <div class="value">{{ $stats['vendors'] }}</div>
            <div class="meta">Active shops</div>
        </article>
        <article class="admin-stat-card">
            <div class="label">Products</div>
            <div class="value">{{ $stats['products'] }}</div>
            <div class="meta">Global catalog items</div>
        </article>
        <article class="admin-stat-card">
            <div class="label">Inventory</div>
            <div class="value">{{ $stats['vendorProducts'] }}</div>
            <div class="meta">Vendor stock rows</div>
        </article>
        <article class="admin-stat-card">
            <div class="label">Orders</div>
            <div class="value">{{ $stats['orders'] }}</div>
            <div class="meta">Placed orders</div>
        </article>
        <article class="admin-stat-card">
            <div class="label">Revenue</div>
            <div class="value">Rs. {{ number_format($stats['revenue']) }}</div>
            <div class="meta">Gross order value</div>
        </article>
    </section>

    <section class="admin-split-grid">
        <article class="admin-card">
            <div class="admin-card-head">
                <h3>Top vendors</h3>
                <p>Stores with mapped delivery locations and active catalog coverage.</p>
            </div>
            <div class="admin-card-body">
                <div class="admin-list">
                    @forelse ($topVendors as $vendor)
                        <div class="admin-list-item">
                            <div>
                                <strong>{{ $vendor['name'] }}</strong>
                                <span>{{ $vendor['address'] ?? 'No address added' }}</span>
                            </div>
                            <div class="admin-badge">{{ count($vendor['locations']) }} locations</div>
                        </div>
                    @empty
                        <div class="admin-muted">No vendors available yet.</div>
                    @endforelse
                </div>
            </div>
        </article>

        <article class="admin-card">
            <div class="admin-card-head">
                <h3>Recent orders</h3>
                <p>Latest checkout activity from the storefront.</p>
            </div>
            <div class="admin-card-body">
                <div class="admin-list">
                    @forelse ($recentOrders as $order)
                        <div class="admin-list-item">
                            <div>
                                <strong>{{ $order['order_number'] }}</strong>
                                <span>{{ $order['customer'] }} · {{ $order['vendor'] }}</span>
                            </div>
                            <div class="admin-badge">{{ ucfirst($order['status']) }}</div>
                        </div>
                    @empty
                        <div class="admin-muted">No orders placed yet.</div>
                    @endforelse
                </div>
            </div>
        </article>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h3>Top inventory items</h3>
            <p>Highlighted vendor-product combinations and stock levels.</p>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Vendor</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topProducts as $product)
                        <tr>
                            <td>{{ $product['product_name'] }}</td>
                            <td>{{ $product['vendor_name'] }}</td>
                            <td>Rs. {{ number_format((int) $product['price']) }}</td>
                            <td>{{ number_format((int) $product['stock']) }}</td>
                            <td><span class="admin-badge {{ $product['is_active'] ? 'success' : 'warning' }}">{{ $product['is_active'] ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-muted">No inventory items available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
