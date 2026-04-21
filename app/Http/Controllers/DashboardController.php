<?php

namespace App\Http\Controllers;

use App\Services\MarketplaceCatalogService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly MarketplaceCatalogService $catalog)
    {
    }

    public function admin(): View
    {
        return view('dashboards.admin', [
            'title' => 'Admin Dashboard',
            'users' => 1240,
            'vendors' => count($this->catalog->vendors()),
            'products' => count($this->catalog->products()),
            'orders' => 318,
            'revenue' => 842560,
        ]);
    }

    public function vendor(): View
    {
        return view('dashboards.vendor', [
            'title' => 'Vendor Dashboard',
            'products' => count($this->catalog->productsForVendor(1)),
            'orders' => 48,
            'sales' => 124300,
            'stockAlerts' => 3,
        ]);
    }

    public function user(): View
    {
        return view('dashboards.user', [
            'title' => 'Customer Dashboard',
            'orders' => 12,
            'wishlist' => 8,
            'offers' => 4,
            'rewards' => 125,
        ]);
    }
}
