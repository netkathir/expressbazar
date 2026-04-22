<?php

use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\RegionZoneController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PanelController::class, 'userHome'])->name('user.home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [AdminAuthController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [PanelController::class, 'dashboard'])->name('dashboard');
        Route::get('/modules/{module}', [PanelController::class, 'module'])->name('module');

        Route::resource('countries', CountryController::class)->except(['show']);
        Route::resource('cities', CityController::class)->except(['show']);
        Route::resource('zones', RegionZoneController::class)->parameters(['zones' => 'zone'])->except(['show']);
        Route::resource('vendors', VendorController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('subcategories', SubcategoryController::class)->except(['show']);
        Route::resource('customers', CustomerController::class);
        Route::resource('taxes', TaxController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::delete('products/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::resource('inventory', InventoryController::class)->except(['show']);
        Route::resource('orders', OrderController::class);
        Route::resource('payments', PaymentController::class)->except(['show']);
        Route::resource('delivery', DeliveryController::class)->except(['show']);
        Route::resource('notifications', NotificationController::class)->except(['show']);
        Route::get('notifications/logs', [NotificationController::class, 'logs'])->name('notifications.logs');
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::get('system-config', [SystemConfigController::class, 'edit'])->name('system-config.edit');
        Route::put('system-config', [SystemConfigController::class, 'update'])->name('system-config.update');
        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::get('vendors/options/cities', [VendorController::class, 'cities'])->name('vendors.cities');
        Route::get('vendors/options/zones', [VendorController::class, 'zones'])->name('vendors.zones');
    });
});
