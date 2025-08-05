<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboard;
use App\Http\Controllers\Shop\DashboardController as ShopDashboard;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/category/{slug}', [HomeController::class, 'category'])->name('category');
         Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product');
         Route::get('/demo', function () {
             return view('demo');
         })->name('demo');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
             Route::put('/password', [AuthController::class, 'changePassword'])->name('password.change');
         });

         // Product routes
         Route::get('/products', [ProductController::class, 'index'])->name('products.index');
         Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
         Route::post('/products', [ProductController::class, 'store'])->name('products.store');
         Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
         Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
         Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
         Route::post('/products/{id}/delete-image', [ProductController::class, 'deleteImage'])->name('products.delete-image');

         // Favorite routes
         Route::post('/favorites/toggle/{productId}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
         Route::delete('/favorites/{productId}', [FavoriteController::class, 'remove'])->name('favorites.remove');

         // Cart routes
         Route::middleware('auth')->group(function () {
             Route::get('/cart', [CartController::class, 'index'])->name('cart');
             Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
             Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity'])->name('cart.update-quantity');
             Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
             Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
             Route::get('/cart/info', [CartController::class, 'getCartInfo'])->name('cart.info');
             Route::get('/cart/validate', [CartController::class, 'validateCart'])->name('cart.validate');
         });

         // Checkout routes
         Route::middleware(['auth', 'role:buyer'])->group(function () {
             Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
             Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
             Route::get('/checkout/confirm/{orderId}', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
         });

         // Buyer routes
         Route::middleware(['auth', 'role:buyer'])->prefix('buyer')->name('buyer.')->group(function () {
             Route::get('/dashboard', [BuyerDashboard::class, 'index'])->name('dashboard');
             Route::get('/orders', [BuyerDashboard::class, 'orders'])->name('orders');
             Route::get('/favorites', [BuyerDashboard::class, 'favorites'])->name('favorites');
             Route::get('/profile', [BuyerDashboard::class, 'profile'])->name('profile');
         });

         // Shop routes
         Route::middleware(['auth', 'role:shop'])->prefix('shop')->name('shop.')->group(function () {
             Route::get('/dashboard', [ShopDashboard::class, 'index'])->name('dashboard');
             Route::get('/setup', [ShopDashboard::class, 'setup'])->name('setup');
             Route::get('/products', [ShopDashboard::class, 'products'])->name('products');
             Route::get('/orders', [ShopDashboard::class, 'orders'])->name('orders');
             Route::get('/analytics', [ShopDashboard::class, 'analytics'])->name('analytics');
         });

// Driver routes
Route::middleware(['auth', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/dashboard', function () {
        return view('driver.dashboard');
    })->name('dashboard');
    
    Route::get('/setup', function () {
        return view('driver.setup');
    })->name('setup');
    
    Route::get('/deliveries', function () {
        return view('driver.deliveries');
    })->name('deliveries');
    
    Route::get('/bids', function () {
        return view('driver.bids');
    })->name('bids');
});

// Technician routes
Route::middleware(['auth', 'role:technician'])->prefix('technician')->name('technician.')->group(function () {
    Route::get('/dashboard', function () {
        return view('technician.dashboard');
    })->name('dashboard');
    
    Route::get('/setup', function () {
        return view('technician.setup');
    })->name('setup');
    
    Route::get('/appointments', function () {
        return view('technician.appointments');
    })->name('appointments');
    
    Route::get('/services', function () {
        return view('technician.services');
    })->name('services');
});

         // Admin routes
         Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
             Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
             Route::get('/users', [AdminDashboard::class, 'users'])->name('users');
             Route::get('/products', [AdminDashboard::class, 'products'])->name('products');
             Route::get('/orders', [AdminDashboard::class, 'orders'])->name('orders');
             Route::get('/shops', [AdminDashboard::class, 'shops'])->name('shops');
             Route::get('/drivers', [AdminDashboard::class, 'drivers'])->name('drivers');
             Route::get('/technicians', [AdminDashboard::class, 'technicians'])->name('technicians');
             Route::get('/analytics', [AdminDashboard::class, 'analytics'])->name('analytics');
         });
