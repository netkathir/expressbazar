@extends('layouts.admin')

@section('content')
    <div class="admin-dashboard">
        <div class="row g-3 align-items-stretch mb-4">
            <div class="col-12 col-xl-8">
                <div class="hero-card admin-analytics-hero h-100 p-4 p-md-5">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <span class="badge rounded-pill text-bg-light mb-3">Analytics overview</span>
                            <h1 class="display-6 fw-bold mb-3">Dashboard performance at a glance.</h1>
                            <p class="text-secondary mb-0">Track orders, revenue, inventory alerts, customer growth and recent payment activity from one place.</p>
                        </div>
                        @canRoute('admin.reports.index')
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-primary rounded-pill px-4">Open reports</a>
                        @endcanRoute
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="soft-panel h-100 p-4">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-secondary small">Revenue Overview</div>
                            <div class="display-6 fw-bold mb-1">{{ \App\Support\StoreCurrency::format($summary['monthly_revenue']) }}</div>
                            <div class="text-secondary small">Monthly revenue from paid orders</div>
                        </div>
                        <span class="admin-insight-icon"><i class="ti ti-chart-line"></i></span>
                    </div>
                    <div class="admin-mini-stat-grid mt-4">
                        <div>
                            <span>Today</span>
                            <strong>{{ \App\Support\StoreCurrency::format($summary['today_sales']) }}</strong>
                        </div>
                        <div>
                            <span>Conversion</span>
                            <strong>{{ number_format((float) $conversionRate['value'], 1) }}%</strong>
                        </div>
                    </div>
                    <div class="text-secondary small mt-3">{{ $conversionRate['note'] }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 admin-kpi-row">
            @foreach ($kpis as $kpi)
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="dashboard-card admin-kpi-card h-100">
                        <div class="text-secondary small">{{ $kpi['label'] }}</div>
                        <div class="h4 fw-bold mb-1">{{ ! empty($kpi['currency']) ? \App\Support\StoreCurrency::format($kpi['value']) : $kpi['value'] }}</div>
                        <div class="text-secondary small">{{ $kpi['hint'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ((int) $summary['low_stock'] > 0)
            <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4">
                <strong>{{ number_format((int) $summary['low_stock']) }} low stock product(s)</strong> require attention.
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                <div class="dashboard-card h-100">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Sales Analytics</h5>
                            <div class="text-secondary small">Revenue and order movement over the last six months.</div>
                        </div>
                    </div>
                    <div class="admin-chart-wrap">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="dashboard-card h-100">
                    <h5 class="fw-bold mb-1">Order Status Analytics</h5>
                    <div class="text-secondary small mb-3">Current order distribution.</div>
                    <div class="admin-chart-wrap admin-chart-wrap-sm admin-doughnut-chart-wrap">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                    <div id="orderStatusLegend" class="admin-chart-legend-grid"></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-4">
                <div class="dashboard-card h-100">
                    <h5 class="fw-bold mb-1">Customer Growth</h5>
                    <div class="text-secondary small mb-3">New customer registrations by month.</div>
                    <div class="admin-chart-wrap admin-chart-wrap-sm">
                        <canvas id="customerGrowthChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="dashboard-card h-100">
                    <h5 class="fw-bold mb-3">Top Selling Products</h5>
                    <div class="d-grid gap-3">
                        @forelse ($topProducts as $item)
                            <div class="admin-product-row">
                                <div>
                                    <div class="fw-semibold">{{ $item->product?->product_name ?? $item->item_name ?? 'Product unavailable' }}</div>
                                    <div class="text-secondary small">Revenue {{ \App\Support\StoreCurrency::format($item->revenue) }}</div>
                                </div>
                                <strong>{{ number_format((int) $item->total_sold) }}</strong>
                            </div>
                        @empty
                            <div class="text-secondary small">No product sales available yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="dashboard-card h-100">
                    <h5 class="fw-bold mb-3">Traffic Sources</h5>
                    @if ($trafficSources['enabled'])
                        <div class="admin-chart-wrap admin-chart-wrap-sm">
                            <canvas id="trafficSourcesChart"></canvas>
                        </div>
                    @else
                        <div class="admin-disabled-analytics">
                            <i class="ti ti-chart-dots"></i>
                            <strong>Traffic analytics disabled</strong>
                            <span>{{ $trafficSources['message'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-4">
                <div class="dashboard-card h-100">
                    <h5 class="fw-bold mb-3">Order Overview</h5>
                    <div class="admin-status-list">
                        <div><span>Completed Orders</span><strong>{{ number_format((int) $summary['completed_orders']) }}</strong></div>
                        <div><span>Cancelled Orders</span><strong>{{ number_format((int) $summary['cancelled_orders']) }}</strong></div>
                        <div><span>Pending Deliveries</span><strong>{{ number_format((int) $summary['pending_deliveries']) }}</strong></div>
                        <div><span>Refund Requests</span><strong>{{ number_format((int) $summary['refund_requests']) }}</strong></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-8">
                <div class="dashboard-card h-100">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Recent Transactions</h5>
                            <div class="text-secondary small">Latest order activity across customers and vendors.</div>
                        </div>
                        @canRoute('admin.orders.index')
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary btn-sm rounded-pill">View orders</a>
                        @endcanRoute
                    </div>
                    <div class="table-responsive admin-transactions-table-wrap">
                        <table class="table align-middle mb-0 admin-transactions-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTransactions as $transaction)
                                    @php($latestPayment = $transaction->payments->sortByDesc('id')->first())
                                    <tr>
                                        <td class="fw-semibold">{{ $transaction->order_number }}</td>
                                        <td>{{ $transaction->customer?->name ?? 'Guest customer' }}</td>
                                        <td>{{ \App\Support\StoreCurrency::format($transaction->total_amount) }}</td>
                                        <td><span class="badge text-bg-light">{{ ucfirst($latestPayment?->status ?? $transaction->payment_status) }}</span></td>
                                        <td><span class="badge text-bg-{{ $transaction->order_status === 'cancelled' ? 'danger' : ($transaction->order_status === 'completed' ? 'success' : 'secondary') }}">{{ ucfirst($transaction->order_status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-4">No recent transactions available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Chart) {
                return;
            }

            const chartColors = {
                primary: '#1F7A63',
                secondary: '#A3D65C',
                accent: '#FF8C42',
                muted: '#DDE7E3',
                dark: '#333333'
            };
            const orderStatusColors = ['#F59E0B', '#3B82F6', '#8BC34A', '#06B6D4', '#0F766E', '#22C55E', '#EF4444'];

            const renderOrderedLegend = (containerId, labels, colors) => {
                const legend = document.getElementById(containerId);

                if (!legend) {
                    return;
                }

                legend.innerHTML = labels.map((label, index) => `
                    <div class="admin-chart-legend-item">
                        <span class="admin-chart-legend-swatch" style="background:${colors[index]};"></span>
                        <span>${label}</span>
                    </div>
                `).join('');
            };

            const monthlyRevenue = @json($monthlyRevenueChart);
            const orderStatus = @json($orderStatusChart);
            const customerGrowth = @json($customerGrowthChart);
            const trafficSources = @json($trafficSources);

            const salesChart = document.getElementById('salesChart');
            if (salesChart) {
                new Chart(salesChart, {
                    type: 'line',
                    data: {
                        labels: monthlyRevenue.labels,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: monthlyRevenue.revenues,
                                borderColor: chartColors.primary,
                                backgroundColor: 'rgba(31, 122, 99, .12)',
                                fill: true,
                                tension: .35
                            },
                            {
                                label: 'Orders',
                                data: monthlyRevenue.orders,
                                borderColor: chartColors.accent,
                                backgroundColor: 'rgba(255, 140, 66, .12)',
                                yAxisID: 'orders',
                                tension: .35
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { bottom: 4 } },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 42,
                                    boxHeight: 12,
                                    padding: 18,
                                    color: '#5F6B7A',
                                    font: { size: 12, weight: '500' }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#667085',
                                    maxRotation: 0,
                                    minRotation: 0,
                                    padding: 8,
                                    font: { size: 12, weight: '500' }
                                },
                                grid: { color: 'rgba(15, 118, 110, .08)' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#667085', padding: 8 },
                                grid: { color: 'rgba(15, 118, 110, .1)' }
                            },
                            orders: {
                                beginAtZero: true,
                                position: 'right',
                                ticks: { color: '#667085', padding: 8 },
                                grid: { drawOnChartArea: false }
                            }
                        }
                    }
                });
            }

            const orderStatusChart = document.getElementById('orderStatusChart');
            if (orderStatusChart) {
                new Chart(orderStatusChart, {
                    type: 'doughnut',
                    data: {
                        labels: orderStatus.labels,
                        datasets: [{
                            data: orderStatus.counts,
                            backgroundColor: orderStatusColors,
                            borderColor: '#FFFFFF',
                            borderWidth: 2,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        cutout: '62%'
                    }
                });
                renderOrderedLegend('orderStatusLegend', orderStatus.labels, orderStatusColors);
            }

            const customerGrowthChart = document.getElementById('customerGrowthChart');
            if (customerGrowthChart) {
                new Chart(customerGrowthChart, {
                    type: 'bar',
                    data: {
                        labels: customerGrowth.labels,
                        datasets: [{
                            label: 'Customers',
                            data: customerGrowth.customers,
                            backgroundColor: 'rgba(31, 122, 99, .82)',
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        layout: { padding: { bottom: 8 } },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: '#667085',
                                    maxRotation: 0,
                                    minRotation: 0,
                                    padding: 10,
                                    font: { size: 12, weight: '500' },
                                    callback: function (value) {
                                        const label = this.getLabelForValue(value);
                                        return label.split(' ');
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#667085', precision: 0, padding: 8 },
                                grid: { color: 'rgba(15, 118, 110, .1)' }
                            }
                        }
                    }
                });
            }

            const trafficSourcesChart = document.getElementById('trafficSourcesChart');
            if (trafficSourcesChart && trafficSources.enabled) {
                new Chart(trafficSourcesChart, {
                    type: 'pie',
                    data: {
                        labels: trafficSources.labels,
                        datasets: [{ data: trafficSources.values }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        });
    </script>
@endpush
