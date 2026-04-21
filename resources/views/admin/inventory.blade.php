@extends('admin.layout')

@section('content')
    <div class="hero-panel" style="margin-bottom: 16px;">
        <div>
            <h1 style="margin: 0 0 8px;">Inventory</h1>
            <p class="admin-muted" style="margin: 0;">Monitor stock levels and reorder thresholds before items go out of stock.</p>
        </div>
    </div>

    <section class="admin-grid cols-3" style="margin-bottom: 20px;">
        <div class="metric"><div class="label">Low stock</div><div class="value">3</div><div class="foot">Immediate reorder needed</div></div>
        <div class="metric"><div class="label">Reserved items</div><div class="value">45</div><div class="foot">Allocated to orders</div></div>
        <div class="metric"><div class="label">Sync status</div><div class="value">Live</div><div class="foot">EposNow-ready sample data</div></div>
    </section>

    <section class="admin-card">
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Stock</th><th>Reserved</th><th>Reorder</th><th>Health</th></tr></thead>
            <tbody>
                @foreach ($inventory as $item)
                    <tr>
                        <td>
                            <div style="display:flex; gap: 12px; align-items:center;">
                                <img class="thumb" src="{{ $item['image'] }}" alt="{{ $item['name'] }}">
                                <div>
                                    <div style="font-weight: 700;">{{ $item['name'] }}</div>
                                    <div class="admin-muted">{{ $item['sku'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $item['stock'] }}</td>
                        <td>{{ $item['reserved'] }}</td>
                        <td>{{ $item['reorder'] }}</td>
                        <td><span class="admin-badge {{ $item['stock'] <= $item['reorder'] ? 'warning' : 'success' }}">{{ $item['stock'] <= $item['reorder'] ? 'Low' : 'Healthy' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
