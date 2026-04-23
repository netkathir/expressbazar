<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\RegionZone;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->buildReportData($request);

        return view('admin.reports.index', array_merge([
            'title' => 'Reports & Analytics',
            'activeMenu' => 'reports',
        ], $data));
    }

    public function export(Request $request)
    {
        $data = $this->buildReportData($request);
        $filename = 'reports-export-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Order Number',
                'Placed At',
                'Customer',
                'Vendor',
                'Country',
                'City',
                'Zone',
                'Order Status',
                'Payment Status',
                'Payment Method',
                'Total Amount',
                'Delivery Charge',
            ]);

            foreach ($data['salesOrders'] as $order) {
                $latestPayment = $order->payments->first();
                $vendor = $order->vendor;

                fputcsv($handle, [
                    $order->order_number,
                    optional($order->placed_at)->format('Y-m-d H:i:s'),
                    $order->customer?->name ?? '-',
                    $vendor?->vendor_name ?? '-',
                    $vendor?->country?->country_name ?? '-',
                    $vendor?->city?->city_name ?? '-',
                    $vendor?->zone?->zone_name ?? '-',
                    ucfirst($order->order_status),
                    ucfirst($latestPayment?->status ?? $order->payment_status),
                    ucfirst($latestPayment?->payment_method ?? '-'),
                    number_format((float) $order->total_amount, 2, '.', ''),
                    number_format((float) $order->delivery_charge, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function buildReportData(Request $request): array
    {
        $filters = $this->validateFilters($request);
        $salesOrders = $this->filteredOrders($filters);

        return [
            'filters' => $filters,
            'summary' => [
                'orders' => Order::count(),
                'revenue' => Order::sum('total_amount'),
                'vendors' => Vendor::count(),
                'active_vendors' => Vendor::where('status', 'active')->count(),
                'products' => Product::count(),
                'active_customers' => User::where('role', 'customer')->where('status', 'active')->count(),
                'payments' => Payment::count(),
                'low_stock' => ProductInventory::query()
                    ->whereNotNull('low_stock_threshold')
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->count(),
            ],
            'filterSummary' => [
                'orders' => $salesOrders->count(),
                'revenue' => $salesOrders->sum('total_amount'),
                'average_order_value' => $salesOrders->count() ? $salesOrders->avg('total_amount') : 0,
            ],
            'salesOrders' => $salesOrders,
            'vendorPerformance' => $this->buildVendorPerformance($salesOrders),
            'inventoryItems' => $this->buildInventoryItems($filters),
            'orderAnalytics' => $this->buildOrderAnalytics($salesOrders),
            'locationReport' => $this->buildLocationReport($salesOrders),
            'recentPayments' => Payment::with('order')
                ->latest()
                ->limit(8)
                ->get(),
            'filterOptions' => [
                'vendors' => Vendor::orderBy('vendor_name')->get(),
                'countries' => Country::orderBy('country_name')->get(),
                'cities' => City::orderBy('city_name')->get(),
                'zones' => RegionZone::orderBy('zone_name')->get(),
            ],
        ];
    }

    private function validateFilters(Request $request): array
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'zone_id' => ['nullable', 'exists:regions_zones,id'],
            'order_status' => ['nullable', Rule::in(['pending', 'accepted', 'processing', 'dispatched', 'delivered', 'completed', 'cancelled'])],
            'inventory_mode' => ['nullable', Rule::in(['internal', 'epos'])],
            'low_stock' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['date_from']) && ! empty($data['date_to']) && $data['date_from'] > $data['date_to']) {
            throw ValidationException::withMessages([
                'date_to' => 'Date to must be on or after date from.',
            ]);
        }

        if (! empty($data['city_id']) && ! empty($data['country_id'])) {
            $city = City::find($data['city_id']);

            if (! $city || (int) $city->country_id !== (int) $data['country_id']) {
                throw ValidationException::withMessages([
                    'city_id' => 'Selected city must belong to the selected country.',
                ]);
            }
        }

        if (! empty($data['zone_id'])) {
            $zone = RegionZone::find($data['zone_id']);

            if (! $zone) {
                throw ValidationException::withMessages([
                    'zone_id' => 'Selected zone is invalid.',
                ]);
            }

            if (! empty($data['country_id']) && (int) $zone->country_id !== (int) $data['country_id']) {
                throw ValidationException::withMessages([
                    'zone_id' => 'Selected zone must belong to the selected country.',
                ]);
            }

            if (! empty($data['city_id']) && (int) $zone->city_id !== (int) $data['city_id']) {
                throw ValidationException::withMessages([
                    'zone_id' => 'Selected zone must belong to the selected city.',
                ]);
            }
        }

        return array_merge([
            'date_from' => null,
            'date_to' => null,
            'vendor_id' => null,
            'country_id' => null,
            'city_id' => null,
            'zone_id' => null,
            'order_status' => null,
            'inventory_mode' => null,
            'low_stock' => false,
        ], $data);
    }

    private function filteredOrders(array $filters): Collection
    {
        return $this->orderQuery($filters)
            ->with(['customer', 'vendor.country', 'vendor.city', 'vendor.zone', 'payments'])
            ->latest('placed_at')
            ->get();
    }

    private function orderQuery(array $filters): Builder
    {
        return Order::query()
            ->when($filters['date_from'], fn (Builder $query, string $dateFrom) => $query->whereDate('placed_at', '>=', $dateFrom))
            ->when($filters['date_to'], fn (Builder $query, string $dateTo) => $query->whereDate('placed_at', '<=', $dateTo))
            ->when($filters['vendor_id'], fn (Builder $query, int $vendorId) => $query->where('vendor_id', $vendorId))
            ->when($filters['order_status'], fn (Builder $query, string $status) => $query->where('order_status', $status))
            ->when($filters['country_id'] || $filters['city_id'] || $filters['zone_id'], function (Builder $query) use ($filters) {
                $query->whereHas('vendor', function (Builder $vendorQuery) use ($filters) {
                    $vendorQuery
                        ->when($filters['country_id'], fn (Builder $subQuery, int $countryId) => $subQuery->where('country_id', $countryId))
                        ->when($filters['city_id'], fn (Builder $subQuery, int $cityId) => $subQuery->where('city_id', $cityId))
                        ->when($filters['zone_id'], fn (Builder $subQuery, int $zoneId) => $subQuery->where('region_zone_id', $zoneId));
                });
            });
    }

    private function buildVendorPerformance(Collection $salesOrders): Collection
    {
        $statsByVendor = $salesOrders
            ->filter(fn (Order $order) => (bool) $order->vendor_id)
            ->groupBy('vendor_id')
            ->map(function (Collection $vendorOrders, int $vendorId) {
                return [
                    'vendor_id' => $vendorId,
                    'orders_count' => $vendorOrders->count(),
                    'revenue' => $vendorOrders->sum('total_amount'),
                ];
            });

        $vendors = Vendor::query()
            ->with(['country', 'city', 'zone'])
            ->orderBy('vendor_name')
            ->get()
            ->map(function (Vendor $vendor) use ($statsByVendor) {
                $stats = $statsByVendor->get($vendor->id, [
                    'orders_count' => 0,
                    'revenue' => 0,
                ]);
                $zone = $vendor->zone;
                $activeZones = ($zone && (($zone->status ?? null) === 'active')) ? 1 : 0;

                return (object) [
                    'vendor_id' => $vendor->id,
                    'vendor_name' => $vendor->vendor_name,
                    'inventory_mode' => $vendor->inventory_mode,
                    'country_name' => $vendor->country?->country_name ?? '-',
                    'city_name' => $vendor->city?->city_name ?? '-',
                    'zone_name' => $zone?->zone_name ?? '-',
                    'active_zones' => $activeZones,
                    'orders_count' => $stats['orders_count'],
                    'revenue' => $stats['revenue'],
                ];
            });

        return $vendors->sortByDesc('revenue')->values();
    }

    private function buildInventoryItems(array $filters): Collection
    {
        return ProductInventory::query()
            ->with(['product.vendor.country', 'product.vendor.city', 'product.vendor.zone'])
            ->when($filters['inventory_mode'], fn (Builder $query, string $mode) => $query->where('inventory_mode', $mode))
            ->when($filters['vendor_id'], fn (Builder $query, int $vendorId) => $query->whereHas('product', fn (Builder $productQuery) => $productQuery->where('vendor_id', $vendorId)))
            ->when($filters['country_id'] || $filters['city_id'] || $filters['zone_id'], function (Builder $query) use ($filters) {
                $query->whereHas('product.vendor', function (Builder $vendorQuery) use ($filters) {
                    $vendorQuery
                        ->when($filters['country_id'], fn (Builder $subQuery, int $countryId) => $subQuery->where('country_id', $countryId))
                        ->when($filters['city_id'], fn (Builder $subQuery, int $cityId) => $subQuery->where('city_id', $cityId))
                        ->when($filters['zone_id'], fn (Builder $subQuery, int $zoneId) => $subQuery->where('region_zone_id', $zoneId));
                });
            })
            ->when($filters['low_stock'], function (Builder $query) {
                $query->whereNotNull('low_stock_threshold')
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
            })
            ->latest()
            ->limit(25)
            ->get();
    }

    private function buildOrderAnalytics(Collection $salesOrders): array
    {
        $statusCounts = $salesOrders->countBy('order_status');

        return [
            'total' => $salesOrders->count(),
            'completed' => (int) $statusCounts->get('completed', 0),
            'cancelled' => (int) $statusCounts->get('cancelled', 0),
            'pending' => (int) $statusCounts->get('pending', 0),
            'accepted' => (int) $statusCounts->get('accepted', 0),
            'processing' => (int) $statusCounts->get('processing', 0),
            'dispatched' => (int) $statusCounts->get('dispatched', 0),
            'delivered' => (int) $statusCounts->get('delivered', 0),
        ];
    }

    private function buildLocationReport(Collection $salesOrders): Collection
    {
        return $salesOrders
            ->filter(fn (Order $order) => $order->vendor)
            ->groupBy(function (Order $order) {
                return implode('|', [
                    $order->vendor?->country?->country_name ?? '-',
                    $order->vendor?->city?->city_name ?? '-',
                    $order->vendor?->zone?->zone_name ?? '-',
                ]);
            })
            ->map(function (Collection $orders, string $key) {
                [$country, $city, $zone] = explode('|', $key);

                return (object) [
                    'country_name' => $country,
                    'city_name' => $city,
                    'zone_name' => $zone,
                    'orders_count' => $orders->count(),
                    'revenue' => $orders->sum('total_amount'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }
}
