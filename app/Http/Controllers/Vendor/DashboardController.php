<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $vendor = Auth::guard('vendor')->user();
        $canViewProducts = $vendor->hasRolePermission('products');
        $canViewOrders = $vendor->hasRolePermission('orders');
        $canViewPayments = $vendor->hasRolePermission('payments');

        return view('vendor.dashboard', [
            'title' => 'Vendor Dashboard',
            'activeMenu' => 'dashboard',
            'canViewProducts' => $canViewProducts,
            'canViewOrders' => $canViewOrders,
            'canViewPayments' => $canViewPayments,
            'showSetupHint' => $canViewProducts && ! $vendor->is_setup_complete && ! $vendor->products()->exists(),
            'productCount' => $canViewProducts ? Product::where('vendor_id', $vendor->id)->count() : null,
            'lowStockCount' => $canViewProducts ? ProductInventory::query()
                ->whereHas('product', fn ($query) => $query->where('vendor_id', $vendor->id))
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count() : null,
            'totalOrderCount' => $canViewOrders ? Order::where('vendor_id', $vendor->id)->count() : null,
            'pendingOrderCount' => $canViewOrders ? Order::where('vendor_id', $vendor->id)->where('order_status', 'pending')->count() : null,
            'activeOrderCount' => $canViewOrders ? Order::where('vendor_id', $vendor->id)->whereNotIn('order_status', ['completed', 'cancelled'])->count() : null,
            'deliveredRevenue' => $canViewOrders ? Order::where('vendor_id', $vendor->id)->where('order_status', 'delivered')->sum('total_amount') : null,
            'recentOrders' => $canViewOrders ? Order::query()
                ->with('customer')
                ->where('vendor_id', $vendor->id)
                ->latest()
                ->limit(5)
                ->get() : collect(),
        ]);
    }
}
