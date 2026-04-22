<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Vendor;

class ReportsController extends Controller
{
    public function index()
    {
        return view('admin.reports.index', [
            'title' => 'Reports & Analytics',
            'activeMenu' => 'reports',
            'summary' => [
                'orders' => Order::count(),
                'revenue' => Order::sum('total_amount'),
                'vendors' => Vendor::count(),
                'active_vendors' => Vendor::where('status', 'active')->count(),
                'products' => Product::count(),
                'payments' => Payment::count(),
                'low_stock' => ProductInventory::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            ],
            'recentOrders' => Order::with(['customer', 'vendor'])->latest()->limit(10)->get(),
            'recentPayments' => Payment::with('order')->latest()->limit(10)->get(),
            'lowStockItems' => ProductInventory::with('product.vendor')
                ->whereNotNull('low_stock_threshold')
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
