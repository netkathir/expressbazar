<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('category.show');
Route::get('/product/{slug}', [StorefrontController::class, 'product'])->name('product.show');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart.show');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.show');
