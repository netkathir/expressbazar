<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\User;
use App\Models\Vendor;

class PanelController extends Controller
{
    public function userHome()
    {
        return view('user.home', [
            'title' => 'User Panel',
            'moduleCount' => count(config('admin_panel.modules', [])),
        ]);
    }

    public function dashboard()
    {
        return view('admin.dashboard', [
            'title' => 'Dashboard',
            'activeMenu' => 'dashboard',
            'panel' => config('admin_panel'),
            ...$this->dashboardAnalytics(),
        ]);
    }

    private function dashboardAnalytics(): array
    {
        $summary = $this->dashboardSummary();

        return [
            'summary' => $summary,
            'kpis' => $this->dashboardKpis($summary),
            'monthlyRevenueChart' => $this->monthlyRevenueChart(),
            'customerGrowthChart' => $this->customerGrowthChart(),
            'orderStatusChart' => $this->orderStatusChart(),
            'topProducts' => $this->topProducts(),
            'recentTransactions' => $this->recentTransactions(),
            'trafficSources' => $this->trafficSources(),
            'conversionRate' => $this->conversionRate($summary),
        ];
    }

    private function dashboardSummary(): array
    {
        $paidOrders = Order::where('payment_status', 'paid');

        return [
            'total_sales' => (clone $paidOrders)->sum('total_amount'),
            'orders' => Order::count(),
            'revenue' => (clone $paidOrders)->sum('total_amount'),
            'vendors' => Vendor::count(),
            'active_vendors' => Vendor::where('status', 'active')->count(),
            'products' => Product::count(),
            'active_customers' => User::where('role', 'customer')->where('status', 'active')->count(),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
            'completed_orders' => Order::whereIn('order_status', ['delivered', 'completed'])->count(),
            'cancelled_orders' => Order::where('order_status', 'cancelled')->count(),
            'low_stock' => ProductInventory::query()
                ->whereNotNull('low_stock_threshold')
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count(),
            'today_sales' => Order::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total_amount'),
            'monthly_revenue' => Order::where('payment_status', 'paid')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount'),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'refund_requests' => Payment::where('status', 'refunded')->count(),
            'pending_deliveries' => Order::whereIn('order_status', ['accepted', 'processing', 'dispatched'])->count(),
        ];
    }

    private function dashboardKpis(array $summary): array
    {
        return [
            ['label' => 'Total Revenue', 'value' => number_format((float) $summary['revenue'], 2), 'hint' => 'Paid order value', 'currency' => true],
            ['label' => 'Orders Today', 'value' => number_format((int) $summary['orders_today']), 'hint' => 'Created today'],
            ['label' => 'Active Users', 'value' => number_format((int) $summary['active_customers']), 'hint' => 'Active customers'],
            ['label' => 'Products Available', 'value' => number_format((int) $summary['products']), 'hint' => 'Catalog products'],
            ['label' => 'Refund Requests', 'value' => number_format((int) $summary['refund_requests']), 'hint' => 'Refunded payments'],
            ['label' => 'Pending Deliveries', 'value' => number_format((int) $summary['pending_deliveries']), 'hint' => 'Accepted to dispatched'],
        ];
    }

    private function monthlyRevenueChart(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $end = now()->endOfMonth();
        $rows = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('YEAR(created_at) as revenue_year, MONTH(created_at) as revenue_month, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->revenue_year, $row->revenue_month));

        $labels = [];
        $revenues = [];
        $orders = [];

        for ($cursor = $start->copy(); $cursor <= $end; $cursor->addMonth()) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('M Y');
            $revenues[] = round((float) ($rows[$key]->revenue ?? 0), 2);
            $orders[] = (int) ($rows[$key]->orders ?? 0);
        }

        return compact('labels', 'revenues', 'orders');
    }

    private function customerGrowthChart(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $end = now()->endOfMonth();
        $rows = User::query()
            ->where('role', 'customer')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('YEAR(created_at) as customer_year, MONTH(created_at) as customer_month, COUNT(*) as total')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->customer_year, $row->customer_month));

        $labels = [];
        $customers = [];

        for ($cursor = $start->copy(); $cursor <= $end; $cursor->addMonth()) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('M Y');
            $customers[] = (int) ($rows[$key]->total ?? 0);
        }

        return compact('labels', 'customers');
    }

    private function orderStatusChart(): array
    {
        $statuses = ['pending', 'accepted', 'processing', 'dispatched', 'delivered', 'completed', 'cancelled'];
        $counts = Order::query()
            ->selectRaw('order_status, COUNT(*) as total')
            ->whereIn('order_status', $statuses)
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        return [
            'labels' => array_map(fn ($status) => ucfirst($status), $statuses),
            'counts' => array_map(fn ($status) => (int) ($counts[$status] ?? 0), $statuses),
        ];
    }

    private function topProducts()
    {
        return OrderItem::query()
            ->select('product_id', 'item_name')
            ->selectRaw('SUM(quantity) as total_sold, SUM(subtotal) as revenue')
            ->groupBy('product_id', 'item_name')
            ->orderByDesc('total_sold')
            ->with('product:id,product_name')
            ->limit(5)
            ->get();
    }

    private function recentTransactions()
    {
        return Order::query()
            ->with(['customer:id,name,email', 'payments' => fn ($query) => $query->latest()->limit(1)])
            ->latest()
            ->limit(10)
            ->get();
    }

    private function trafficSources(): array
    {
        return [
            'enabled' => false,
            'labels' => ['Direct', 'Search', 'Social', 'Referral'],
            'values' => [0, 0, 0, 0],
            'message' => 'Traffic tracking is not configured yet.',
        ];
    }

    private function conversionRate(array $summary): array
    {
        $customers = max((int) $summary['active_customers'], 0);

        return [
            'label' => 'Customer order conversion',
            'value' => $customers > 0 ? round(((int) $summary['orders'] / $customers) * 100, 1) : 0,
            'note' => 'Based on orders compared with active customers.',
        ];
    }

    public function module(string $module)
    {
        $moduleConfig = config("admin_panel.modules.$module");

        abort_if(!$moduleConfig, 404);

        if (! empty($moduleConfig['crud_route'])) {
            return redirect()->route($moduleConfig['crud_route']);
        }

        return view('admin.module', [
            'title' => $moduleConfig['title'],
            'activeMenu' => $module,
            'moduleKey' => $module,
            'module' => $moduleConfig,
        ]);
    }
}
