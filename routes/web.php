<?php

use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\RegionZoneController;
use App\Http\Controllers\AdminPasswordResetController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerPasswordResetController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('user.home');
Route::get('/login', [CustomerAuthController::class, 'createLogin'])->name('storefront.login');
Route::post('/login', [CustomerAuthController::class, 'storeLogin'])->name('storefront.login.store');
Route::get('/register', [CustomerAuthController::class, 'createRegister'])->name('storefront.register');
Route::post('/register', [CustomerAuthController::class, 'storeRegister'])->name('storefront.register.store');
Route::get('/otp', [CustomerAuthController::class, 'otpForm'])->name('storefront.otp.form');
Route::post('/otp/verify', [CustomerAuthController::class, 'verifyOtp'])->name('storefront.otp.verify');
Route::post('/otp/resend', [CustomerAuthController::class, 'resendOtp'])->name('storefront.otp.resend');
Route::get('/forgot-password', [CustomerPasswordResetController::class, 'create'])->name('storefront.password.request');
Route::post('/forgot-password/send-otp', [CustomerPasswordResetController::class, 'sendOtp'])->name('storefront.password.send-otp');
Route::get('/forgot-password/otp', [CustomerPasswordResetController::class, 'otpForm'])->name('storefront.password.otp.form');
Route::post('/forgot-password/verify-otp', [CustomerPasswordResetController::class, 'verifyOtp'])->name('storefront.password.verify-otp');
Route::get('/forgot-password/reset', [CustomerPasswordResetController::class, 'createResetForm'])->name('storefront.password.reset.form');
Route::post('/forgot-password/reset', [CustomerPasswordResetController::class, 'resetPassword'])->name('storefront.password.reset');
Route::post('/logout', [CustomerAuthController::class, 'destroy'])->name('storefront.logout');
Route::get('/account', [CustomerAccountController::class, 'index'])->middleware('auth')->name('storefront.account');
Route::get('/account/profile/edit', [CustomerAccountController::class, 'editProfile'])->middleware('auth')->name('storefront.profile.edit');
Route::put('/account/profile', [CustomerAccountController::class, 'updateProfile'])->middleware('auth')->name('storefront.profile.update');
Route::get('/account/orders', [CustomerAccountController::class, 'orders'])->middleware('auth')->name('storefront.orders.index');
Route::get('/account/orders/{order}/success', [CustomerAccountController::class, 'showOrderSuccess'])->middleware('auth')->name('storefront.orders.success');
Route::get('/account/orders/{order}/payment-cancelled', [CustomerAccountController::class, 'cancelPayment'])->middleware('auth')->name('storefront.orders.cancel');
Route::post('/account/addresses', [CustomerAccountController::class, 'storeAddress'])->middleware('auth')->name('storefront.addresses.store');
Route::get('/account/addresses/{address}/edit', [CustomerAccountController::class, 'editAddress'])->middleware('auth')->name('storefront.addresses.edit');
Route::put('/account/addresses/{address}', [CustomerAccountController::class, 'updateAddress'])->middleware('auth')->name('storefront.addresses.update');
Route::delete('/account/addresses/{address}', [CustomerAccountController::class, 'destroyAddress'])->middleware('auth')->name('storefront.addresses.destroy');
Route::get('/account/orders/{order}', [CustomerAccountController::class, 'showOrder'])->middleware('auth')->name('storefront.orders.show');
Route::post('/account/orders/{order}/retry-payment', [CustomerAccountController::class, 'retryPayment'])->middleware('auth')->name('storefront.orders.retry-payment');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->middleware('auth')->name('storefront.checkout');
Route::post('/checkout/place-order', [StorefrontController::class, 'placeOrder'])->middleware('auth')->name('storefront.checkout.place');
Route::get('/payments/checkout/{order}', [PaymentController::class, 'checkout'])->middleware('auth')->name('payments.checkout');
Route::post('/stripe/webhook', [PaymentController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('stripe.webhook');
Route::get('/categories/{category}', [StorefrontController::class, 'category'])->name('storefront.category');
Route::get('/subcategories/{subcategory}', [StorefrontController::class, 'subcategory'])->name('storefront.subcategory');
Route::get('/products/{product}', [StorefrontController::class, 'product'])->name('storefront.product');
Route::get('/search', [StorefrontController::class, 'search'])->name('storefront.search');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('storefront.cart');
Route::post('/location', [StorefrontController::class, 'setLocation'])->name('storefront.location');
Route::get('/location/cities', [StorefrontController::class, 'cities'])->name('storefront.location.cities');
Route::get('/location/zones', [StorefrontController::class, 'zones'])->name('storefront.location.zones');
Route::post('/cart/items/{product}', [StorefrontController::class, 'addToCart'])->name('storefront.cart.add');
Route::patch('/cart/items/{product}', [StorefrontController::class, 'updateCart'])->name('storefront.cart.update');
Route::delete('/cart/items/{product}', [StorefrontController::class, 'removeFromCart'])->name('storefront.cart.remove');
Route::post('/cart/clear', [StorefrontController::class, 'clearCart'])->name('storefront.cart.clear');
Route::post('/cart/merge', [StorefrontController::class, 'mergeGuestCart'])->middleware('auth')->name('storefront.cart.merge');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
        Route::get('/forgot-password', [AdminPasswordResetController::class, 'create'])->name('password.request');
        Route::post('/forgot-password/send-otp', [AdminPasswordResetController::class, 'sendOtp'])->name('password.send-otp');
        Route::get('/forgot-password/otp', [AdminPasswordResetController::class, 'otpForm'])->name('password.otp.form');
        Route::post('/forgot-password/verify-otp', [AdminPasswordResetController::class, 'verifyOtp'])->name('password.verify-otp');
        Route::get('/forgot-password/reset', [AdminPasswordResetController::class, 'createResetForm'])->name('password.reset.form');
        Route::post('/forgot-password/reset', [AdminPasswordResetController::class, 'resetPassword'])->name('password.reset');
    });

    Route::post('/logout', [AdminAuthController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [PanelController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->name('dashboard.alias');
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
        Route::resource('payments', AdminPaymentController::class)->except(['show']);
        Route::resource('delivery', DeliveryController::class)->except(['show']);
        Route::resource('notifications', NotificationController::class)->except(['show']);
        Route::get('notifications/logs', [NotificationController::class, 'logs'])->name('notifications.logs');
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::get('system-config', [SystemConfigController::class, 'edit'])->name('system-config.edit');
        Route::put('system-config', [SystemConfigController::class, 'update'])->name('system-config.update');
        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportsController::class, 'export'])->name('reports.export');
        Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::get('vendors/options/cities', [VendorController::class, 'cities'])->name('vendors.cities');
        Route::get('vendors/options/zones', [VendorController::class, 'zones'])->name('vendors.zones');
    });
});
