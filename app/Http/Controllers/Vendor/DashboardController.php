<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $vendor = Auth::guard('vendor')->user();
        $canViewProducts = $vendor->hasRolePermission('products');
        $canViewOrders = $vendor->hasRolePermission('orders');

        return view('vendor.dashboard', [
            'title' => 'Vendor Dashboard',
            'activeMenu' => 'dashboard',
            'canViewProducts' => $canViewProducts,
            'canViewOrders' => $canViewOrders,
            'productCount' => $canViewProducts ? Product::where('vendor_id', $vendor->id)->count() : null,
            'pendingOrderCount' => $canViewOrders ? Order::where('vendor_id', $vendor->id)->where('order_status', 'pending')->count() : null,
            'activeOrderCount' => $canViewOrders ? Order::where('vendor_id', $vendor->id)->whereNotIn('order_status', ['completed', 'cancelled'])->count() : null,
            'recentOrders' => $canViewOrders ? Order::query()
                ->with('customer')
                ->where('vendor_id', $vendor->id)
                ->latest()
                ->limit(5)
                ->get() : collect(),
        ]);
    }
}
