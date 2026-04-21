<?php

use App\Http\Controllers\Admin\AdminPanelController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/browse', [StorefrontController::class, 'browseAll'])->name('catalog.browse');
Route::get('/vendors/{slug}', [StorefrontController::class, 'vendor'])->name('vendor.show');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('category.show');
Route::get('/subcategory/{slug}', [StorefrontController::class, 'subcategory'])->name('subcategory.show');
Route::get('/product/{slug}', [StorefrontController::class, 'product'])->name('product.show');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart.show');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.show');
Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('checkout.place');
Route::get('/order/success/{orderNumber}', [StorefrontController::class, 'success'])->name('order.success');
Route::post('/cart/add/{slug}', [StorefrontController::class, 'addToCart'])->name('cart.add');
Route::delete('/cart/{productId}', [StorefrontController::class, 'removeFromCart'])->name('cart.remove');
Route::delete('/cart', [StorefrontController::class, 'clearCart'])->name('cart.clear');
Route::post('/cart/coupon', [StorefrontController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [StorefrontController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::get('/login', [CustomerAuthController::class, 'createLogin'])->name('login');
Route::post('/login', [CustomerAuthController::class, 'login'])->name('login.submit');
Route::get('/register', [CustomerAuthController::class, 'createRegister'])->name('register');
Route::post('/register', [CustomerAuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
Route::get('/my-orders', [StorefrontController::class, 'myOrders'])->middleware('auth')->name('orders.mine');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->middleware(['auth', 'admin'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('index');
        Route::get('/dashboard', [AdminPanelController::class, 'dashboard'])->name('dashboard');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/subcategories', [SubcategoryController::class, 'index'])->name('subcategories');
        Route::get('/subcategories/create', [SubcategoryController::class, 'create'])->name('subcategories.create');
        Route::post('/subcategories', [SubcategoryController::class, 'store'])->name('subcategories.store');
        Route::get('/subcategories/{subcategory}/edit', [SubcategoryController::class, 'edit'])->name('subcategories.edit');
        Route::put('/subcategories/{subcategory}', [SubcategoryController::class, 'update'])->name('subcategories.update');
        Route::delete('/subcategories/{subcategory}', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');

        Route::get('/vendors', [VendorController::class, 'index'])->name('vendors');
        Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');

        Route::get('/locations', [AdminPanelController::class, 'locations'])->name('locations');
        Route::get('/products', [ProductController::class, 'index'])->name('products');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
        Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{inventory}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

        Route::get('/orders', [AdminPanelController::class, 'orders'])->name('orders');
    });
});
