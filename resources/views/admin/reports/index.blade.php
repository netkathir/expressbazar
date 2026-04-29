@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1 class="h3 mb-1">Reports & Analytics</h1>
            </div>
            <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn btn-primary">
                Export CSV
            </a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($filterOptions['vendors'] as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->vendor_name }}</option>
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
                <div class="col-md-3">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($filterOptions['countries'] as $country)
                            <option value="{{ $country->id }}" @selected((string) request('country_id') === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <select name="city_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($filterOptions['cities'] as $city)
                            <option value="{{ $city->id }}" @selected((string) request('city_id') === (string) $city->id)>{{ $city->city_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Zone</label>
                    <select name="zone_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($filterOptions['zones'] as $zone)
                            <option value="{{ $zone->id }}" @selected((string) request('zone_id') === (string) $zone->id)>{{ $zone->zone_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Inventory Mode</label>
                    <select name="inventory_mode" class="form-select">
                        <option value="">All</option>
                        <option value="internal" @selected(request('inventory_mode') === 'internal')>Internal</option>
                        <option value="epos" @selected(request('inventory_mode') === 'epos')>EPOS</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="lowStock" name="low_stock" value="1" @checked(request('low_stock'))>
                        <label class="form-check-label" for="lowStock">Low stock only</label>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-dark" type="submit">Apply Filters</button>
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card shell-card h-100"><div class="card-body p-4"><div class="text-secondary small">Orders</div><div class="h3 mb-0">{{ $summary['orders'] }}</div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card shell-card h-100"><div class="card-body p-4"><div class="text-secondary small">Revenue</div><div class="h3 mb-0">{{ number_format((float) $summary['revenue'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card shell-card h-100"><div class="card-body p-4"><div class="text-secondary small">Active Vendors</div><div class="h3 mb-0">{{ $summary['active_vendors'] }} / {{ $summary['vendors'] }}</div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card shell-card h-100"><div class="card-body p-4"><div class="text-secondary small">Products</div><div class="h3 mb-0">{{ $summary['products'] }}</div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card shell-card h-100"><div class="card-body p-4"><div class="text-secondary small">Active Customers</div><div class="h3 mb-0">{{ $summary['active_customers'] }}</div></div></div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card shell-card h-100"><div class="card-body p-4"><div class="text-secondary small">Low Stock</div><div class="h3 mb-0">{{ $summary['low_stock'] }}</div></div></div>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Filtered Sales Snapshot</h2>
                    <p class="text-secondary mb-0">Filtered by date, vendor and location.</p>
                </div>
                <div class="d-flex gap-3 flex-wrap">
                    <div class="soft-card px-3 py-2">
                        <div class="small text-secondary">Orders</div>
                        <div class="fw-semibold">{{ $filterSummary['orders'] }}</div>
                    </div>
                    <div class="soft-card px-3 py-2">
                        <div class="small text-secondary">Revenue</div>
                        <div class="fw-semibold">{{ number_format((float) $filterSummary['revenue'], 2) }}</div>
                    </div>
                    <div class="soft-card px-3 py-2">
                        <div class="small text-secondary">Average Order</div>
                        <div class="fw-semibold">{{ number_format((float) $filterSummary['average_order_value'], 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Placed</th>
                            <th>Customer</th>
                            <th>Vendor</th>
                            <th>Location</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salesOrders as $order)
                            @php($latestPayment = $order->payments->first())
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>{{ optional($order->placed_at)->format('d M Y, h:i A') }}</td>
                                <td>{{ $order->customer?->name ?? '-' }}</td>
                                <td>{{ $order->vendor?->vendor_name ?? '-' }}</td>
                                <td>
                                    <div>{{ $order->vendor?->country?->country_name ?? '-' }}</div>
                                    <div class="small text-secondary">{{ $order->vendor?->city?->city_name ?? '-' }} / {{ $order->vendor?->zone?->zone_name ?? '-' }}</div>
                                </td>
                                <td><span class="badge text-bg-light">{{ ucfirst($latestPayment?->status ?? $order->payment_status) }}</span></td>
                                <td><span class="badge text-bg-{{ $order->order_status === 'completed' ? 'success' : ($order->order_status === 'cancelled' ? 'danger' : 'secondary') }}">{{ ucfirst($order->order_status) }}</span></td>
                                <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary" aria-label="View order" title="View order">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-5">No sales records found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Vendor Performance</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Mode</th>
                                    <th>Location</th>
                                    <th>Active Zones</th>
                                    <th>Zone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vendorPerformance as $row)
                                    <tr>
                                        <td>{{ $row->vendor_name }}</td>
                                        <td>{{ $row->orders_count }}</td>
                                        <td>{{ number_format((float) $row->revenue, 2) }}</td>
                                        <td><span class="badge text-bg-{{ $row->inventory_mode === 'epos' ? 'info' : 'primary' }}">{{ strtoupper($row->inventory_mode) }}</span></td>
                                        <td>
                                            <div>{{ $row->country_name }}</div>
                                            <div class="small text-secondary">{{ $row->city_name }}</div>
                                        </td>
                                        <td>{{ $row->active_zones }}</td>
                                        <td>{{ $row->zone_name }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-4">No vendor performance data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Order Analytics</h2>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="soft-card p-3 h-100">
                                <div class="small text-secondary">Total</div>
                                <div class="h4 mb-0">{{ $orderAnalytics['total'] }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="soft-card p-3 h-100">
                                <div class="small text-secondary">Pending</div>
                                <div class="h4 mb-0">{{ $orderAnalytics['pending'] }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="soft-card p-3 h-100">
                                <div class="small text-secondary">Completed</div>
                                <div class="h4 mb-0">{{ $orderAnalytics['completed'] }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="soft-card p-3 h-100">
                                <div class="small text-secondary">Cancelled</div>
                                <div class="h4 mb-0">{{ $orderAnalytics['cancelled'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ([
                                    'accepted' => $orderAnalytics['accepted'],
                                    'processing' => $orderAnalytics['processing'],
                                    'dispatched' => $orderAnalytics['dispatched'],
                                    'delivered' => $orderAnalytics['delivered'],
                                ] as $label => $count)
                                    <tr>
                                        <td>{{ ucfirst($label) }}</td>
                                        <td>{{ $count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Inventory Status</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Vendor</th>
                                    <th>Stock</th>
                                    <th>Unit</th>
                                    <th>Mode</th>
                                    <th>Sync Status</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inventoryItems as $inventory)
                                    <tr>
                                        <td class="fw-semibold">{{ $inventory->product?->product_name ?? '-' }}</td>
                                        <td>{{ $inventory->product?->vendor?->vendor_name ?? '-' }}</td>
                                        <td>
                                            {{ $inventory->stock_quantity }}
                                            @if (! is_null($inventory->low_stock_threshold) && $inventory->stock_quantity <= $inventory->low_stock_threshold)
                                                <span class="badge text-bg-warning ms-2">Low</span>
                                            @endif
                                        </td>
                                        <td>{{ $inventory->unit ?? '-' }}</td>
                                        <td><span class="badge text-bg-{{ $inventory->inventory_mode === 'epos' ? 'info' : 'primary' }}">{{ strtoupper($inventory->inventory_mode) }}</span></td>
                                        <td>{{ $inventory->sync_status ?: '-' }}</td>
                                        <td>{{ $inventory->last_synced_at?->format('d M Y, h:i A') ?? $inventory->updated_at?->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-4">No inventory items found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Recent Payments</h2>
                    @forelse ($recentPayments as $payment)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold">{{ $payment->transaction_id }}</div>
                            <div class="small text-secondary">
                                {{ $payment->order?->order_number ?? '-' }} - {{ ucfirst($payment->status) }}
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No payment records yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card shell-card">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Location Based Revenue</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Country</th>
                            <th>City</th>
                            <th>Zone</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locationReport as $row)
                            <tr>
                                <td>{{ $row->country_name }}</td>
                                <td>{{ $row->city_name }}</td>
                                <td>{{ $row->zone_name }}</td>
                                <td>{{ $row->orders_count }}</td>
                                <td>{{ number_format((float) $row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">No location analytics available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
