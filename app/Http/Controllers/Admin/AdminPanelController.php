<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPanelController extends Controller
{
    public function dashboard(): View
    {
        $topVendors = $this->vendorQuery()->take(4)->get()->map(fn (Vendor $vendor) => [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'address' => $vendor->address,
            'locations' => $vendor->locations->map(fn (Location $location) => $location->city . ' - ' . $location->pincode)->all(),
        ]);

        $recentOrders = $this->orderQuery()->take(5)->get()->map(fn (Order $order) => [
            'order_number' => $order->order_number,
            'customer' => $order->shipping_name ?? 'Customer',
            'status' => $order->status,
            'grand_total' => $order->grand_total,
            'vendor' => $order->vendor?->name ?? 'Vendor',
        ]);

        $topProducts = $this->vendorProductQuery()->take(6)->get()->map(fn (VendorProduct $item) => [
            'vendor_name' => $item->vendor?->name ?? 'Vendor',
            'product_name' => $item->product?->name ?? 'Product',
            'price' => $item->price,
            'stock' => $item->stock,
            'is_active' => $item->is_active,
        ]);

        return view('admin.dashboard', [
            'pageTitle' => 'Admin Dashboard | ExpressBazar',
            'stats' => [
                'locations' => $this->countTable('locations'),
                'categories' => $this->countTable('categories'),
                'subcategories' => $this->countTable('subcategories'),
                'vendors' => $this->countTable('vendors'),
                'products' => $this->countTable('products'),
                'vendorProducts' => $this->countTable('vendor_products'),
                'orders' => $this->countTable('orders'),
                'revenue' => Schema::hasTable('orders') ? (int) Order::query()->sum('grand_total') : 0,
            ],
            'recentOrders' => $recentOrders->all(),
            'topVendors' => $topVendors->all(),
            'topProducts' => $topProducts->all(),
        ]);
    }

    public function vendors(): View
    {
        return view('admin.vendors', [
            'pageTitle' => 'Vendors | Admin | ExpressBazar',
            'vendors' => $this->vendorQuery()->paginate(10),
        ]);
    }

    public function locations(): View
    {
        return view('admin.locations', [
            'pageTitle' => 'Locations | Admin | ExpressBazar',
            'locations' => $this->locationQuery()->paginate(12),
        ]);
    }

    public function products(): View
    {
        return view('admin.products', [
            'pageTitle' => 'Products | Admin | ExpressBazar',
            'products' => $this->vendorProductQuery()->paginate(10),
        ]);
    }

    public function orders(): View
    {
        return view('admin.orders', [
            'pageTitle' => 'Orders | Admin | ExpressBazar',
            'orders' => $this->orderQuery()->paginate(12),
        ]);
    }

    private function locationQuery()
    {
        if (Schema::hasTable('locations')) {
            return Location::query()
                ->withCount('vendors')
                ->orderBy('city')
                ->orderBy('pincode');
        }

        return Location::query()->whereRaw('1 = 0');
    }

    private function vendorQuery()
    {
        if (Schema::hasTable('vendors')) {
            return Vendor::query()->with(['locations'])->withCount('products')->orderBy('name');
        }

        return Vendor::query()->whereRaw('1 = 0');
    }

    private function vendorProductQuery()
    {
        if (Schema::hasTable('vendor_products')) {
            return VendorProduct::query()->with(['vendor', 'product.subcategory.category'])->latest('id');
        }

        return VendorProduct::query()->whereRaw('1 = 0');
    }

    private function orderQuery()
    {
        if (Schema::hasTable('orders')) {
            return Order::query()->with(['items.product', 'vendor'])->latest();
        }

        return Order::query()->whereRaw('1 = 0');
    }

    private function locationsList()
    {
        if (Schema::hasTable('locations')) {
            return Location::query()->orderBy('city')->orderBy('pincode')->get()->map(fn (Location $location) => [
                'id' => $location->id,
                'city' => $location->city,
                'pincode' => $location->pincode,
                'vendors' => $location->vendors()->count(),
            ]);
        }

        return collect([
            ['id' => 1, 'city' => 'Chennai', 'pincode' => '600001', 'vendors' => 1],
            ['id' => 2, 'city' => 'Chennai', 'pincode' => '600020', 'vendors' => 2],
            ['id' => 3, 'city' => 'Bangalore', 'pincode' => '560001', 'vendors' => 1],
        ]);
    }

    private function vendorList()
    {
        if (Schema::hasTable('vendors')) {
            return Vendor::query()->with('locations')->orderBy('name')->get()->map(fn (Vendor $vendor) => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'slug' => $vendor->slug,
                'address' => $vendor->address,
                'rating' => $vendor->rating,
                'products' => $vendor->products()->count(),
                'locations' => $vendor->locations->map(fn (Location $location) => $location->city . ' - ' . $location->pincode)->all(),
            ]);
        }

        return collect();
    }

    private function vendorProducts()
    {
        if (Schema::hasTable('vendor_products')) {
            return VendorProduct::query()
                ->with(['vendor', 'product'])
                ->orderByDesc('id')
                ->get()
                ->map(fn (VendorProduct $item) => [
                    'id' => $item->id,
                    'vendor_name' => $item->vendor?->name ?? 'Vendor',
                    'product_name' => $item->product?->name ?? 'Product',
                    'price' => $item->price,
                    'stock' => $item->stock,
                    'is_active' => $item->is_active,
                    'image' => $item->product?->image_url ? asset($item->product->image_url) : asset('admin/assets/images/product-1.png'),
                ]);
        }

        return collect();
    }

    private function orderList()
    {
        if (Schema::hasTable('orders')) {
            return Order::query()
                ->with(['items.product', 'vendor'])
                ->latest()
                ->get()
                ->map(fn (Order $order) => [
                    'order_number' => $order->order_number,
                    'customer' => $order->shipping_name ?? 'Customer',
                    'status' => $order->status,
                    'grand_total' => $order->grand_total,
                    'items_count' => $order->items->count(),
                    'vendor' => $order->vendor?->name ?? 'Vendor',
                    'address' => $order->shipping_address,
                ]);
        }

        return collect();
    }

    private function countTable(string $table): int
    {
        return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
    }
}
