<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\DeliveryConfig;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\RegionZone;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferenceModuleController extends Controller
{
    public function countries(Request $request)
    {
        $rows = Country::query()
            ->withCount('cities')
            ->when($request->filled('search'), fn ($query) => $this->search($query, ['country_name', 'country_code', 'currency'], $request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Country Management', 'countries', $rows, [
            'Country' => fn ($row) => $row->country_name,
            'Code' => fn ($row) => $row->country_code,
            'Currency' => fn ($row) => $row->currency,
            'Cities' => fn ($row) => $row->cities_count,
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function cities(Request $request)
    {
        $rows = City::query()
            ->with('country')
            ->when($request->filled('search'), fn ($query) => $this->search($query, ['city_name', 'city_code', 'state'], $request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('City Management', 'cities', $rows, [
            'City' => fn ($row) => $row->city_name,
            'Code' => fn ($row) => $row->city_code,
            'State' => fn ($row) => $row->state ?: '-',
            'Country' => fn ($row) => $row->country?->country_name ?: '-',
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function zones(Request $request)
    {
        $rows = RegionZone::query()
            ->with(['country', 'city'])
            ->when($request->filled('search'), fn ($query) => $this->search($query, ['zone_name', 'zone_code'], $request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Region / Zone Management', 'zones', $rows, [
            'Zone' => fn ($row) => $row->zone_name,
            'Code' => fn ($row) => $row->zone_code,
            'City' => fn ($row) => $row->city?->city_name ?: '-',
            'Country' => fn ($row) => $row->country?->country_name ?: '-',
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function categories(Request $request)
    {
        $rows = Category::query()
            ->when($request->filled('search'), fn ($query) => $this->search($query, ['category_name'], $request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Category Management', 'categories', $rows, [
            'Category' => fn ($row) => $row->category_name,
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function subcategories(Request $request)
    {
        $rows = Subcategory::query()
            ->with('category')
            ->when($request->filled('search'), fn ($query) => $this->search($query, ['subcategory_name'], $request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Subcategory Management', 'subcategories', $rows, [
            'Subcategory' => fn ($row) => $row->subcategory_name,
            'Category' => fn ($row) => $row->category?->category_name ?: '-',
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function customers(Request $request)
    {
        $customerIds = Order::query()
            ->where('vendor_id', Auth::guard('vendor')->id())
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        $rows = User::query()
            ->whereIn('id', $customerIds)
            ->when($request->filled('search'), fn ($query) => $this->search($query, ['name', 'email', 'phone'], $request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Customer Management', 'customers', $rows, [
            'Customer' => fn ($row) => $row->name,
            'Email' => fn ($row) => $row->email,
            'Phone' => fn ($row) => $row->phone ?: '-',
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function taxes(Request $request)
    {
        $rows = Tax::query()
            ->when($request->filled('search'), fn ($query) => $this->search($query, ['tax_name'], $request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Tax Management', 'taxes', $rows, [
            'Tax' => fn ($row) => $row->tax_name,
            'Percentage' => fn ($row) => rtrim(rtrim(number_format((float) $row->tax_percentage, 2), '0'), '.').'%',
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function inventory(Request $request)
    {
        $vendorId = Auth::guard('vendor')->id();
        $rows = ProductInventory::query()
            ->with(['product.vendor'])
            ->whereHas('product', fn ($query) => $query->where('vendor_id', $vendorId))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->whereHas('product', fn ($sub) => $sub->where('product_name', 'like', "%{$search}%"));
            })
            ->when($request->filled('inventory_mode'), fn ($query) => $query->where('inventory_mode', $request->string('inventory_mode')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Inventory Management', 'inventory', $rows, [
            'Product' => fn ($row) => $row->product?->product_name ?: '-',
            'Mode' => fn ($row) => strtoupper($row->inventory_mode),
            'Stock' => fn ($row) => $row->stock_quantity,
            'Unit' => fn ($row) => $row->unit ?: '-',
            'Sync' => fn ($row) => $row->sync_status ?: '-',
        ]);
    }

    public function delivery(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        $rows = DeliveryConfig::query()
            ->with(['country', 'city', 'zone'])
            ->where('country_id', $vendor->country_id)
            ->where('city_id', $vendor->city_id)
            ->where('zone_id', $vendor->region_zone_id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Delivery & Logistics', 'delivery', $rows, [
            'Country' => fn ($row) => $row->country?->country_name ?: '-',
            'City' => fn ($row) => $row->city?->city_name ?: '-',
            'Zone' => fn ($row) => $row->zone?->zone_name ?: '-',
            'Charge' => fn ($row) => number_format((float) $row->delivery_charge, 2),
            'Available' => fn ($row) => $row->delivery_available ? 'Yes' : 'No',
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function notifications(Request $request)
    {
        $rows = NotificationTemplate::query()
            ->when($request->filled('search'), fn ($query) => $this->search($query, ['template_name', 'notification_type'], $request))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return $this->table('Notification Management', 'notifications', $rows, [
            'Template' => fn ($row) => $row->template_name,
            'Type' => fn ($row) => $row->notification_type,
            'Channel' => fn ($row) => ucfirst($row->channel),
            'Status' => fn ($row) => ucfirst($row->status),
        ]);
    }

    public function reports()
    {
        $vendorId = Auth::guard('vendor')->id();
        $rows = collect([
            (object) ['metric' => 'Total Orders', 'value' => Order::where('vendor_id', $vendorId)->count()],
            (object) ['metric' => 'Delivered Revenue', 'value' => number_format((float) Order::where('vendor_id', $vendorId)->where('order_status', 'delivered')->sum('total_amount'), 2)],
            (object) ['metric' => 'Products', 'value' => Product::where('vendor_id', $vendorId)->count()],
            (object) ['metric' => 'Low Stock Items', 'value' => ProductInventory::whereHas('product', fn ($query) => $query->where('vendor_id', $vendorId))->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count()],
        ]);

        return view('vendor.reference-index', [
            'title' => 'Reports & Analytics',
            'activeMenu' => 'reports',
            'rows' => $rows,
            'columns' => [
                'Metric' => fn ($row) => $row->metric,
                'Value' => fn ($row) => $row->value,
            ],
            'routeName' => 'vendor.reports.index',
            'showFilters' => false,
        ]);
    }

    private function table(string $title, string $activeMenu, $rows, array $columns)
    {
        return view('vendor.reference-index', [
            'title' => $title,
            'activeMenu' => $activeMenu,
            'rows' => $rows,
            'columns' => $columns,
            'routeName' => 'vendor.'.$activeMenu.'.index',
            'showFilters' => true,
        ]);
    }

    private function search(Builder $query, array $columns, Request $request): void
    {
        $search = trim((string) $request->string('search'));

        $query->where(function ($subQuery) use ($columns, $search) {
            foreach ($columns as $column) {
                $subQuery->orWhere($column, 'like', "%{$search}%");
            }
        });
    }
}
