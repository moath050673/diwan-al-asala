<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

// ---------- الواجهة الأمامية ----------
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/products', [PageController::class, 'products'])->name('products');
Route::get('/product/{id}', [PageController::class, 'product'])->whereNumber('id')->name('product');
Route::get('/categories', [PageController::class, 'categories'])->name('categories');
Route::get('/cart', [PageController::class, 'cart'])->name('cart');
Route::get('/checkout', [PageController::class, 'checkout'])->name('checkout');
Route::get('/order-success', [PageController::class, 'orderSuccess'])->name('order-success');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

// ---------- SEO ----------
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('sitemap');

// ---------- الملفات العامة المخزنة في قاعدة البيانات (صور المنتجات) ----------
Route::get('/media/{path}', [\App\Http\Controllers\MediaController::class, 'show'])
    ->where('path', '[A-Za-z0-9_\-./]+')
    ->name('media');

// ---------- لوحة التحكم ----------
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [PageController::class, 'adminLogin'])->name('login');
    Route::get('/dashboard', [PageController::class, 'adminDashboard'])->name('dashboard');
    Route::get('/products', [PageController::class, 'adminProducts'])->name('products');
    Route::get('/orders', [PageController::class, 'adminOrders'])->name('orders');
    Route::get('/customers', [PageController::class, 'adminCustomers'])->name('customers');
    Route::get('/categories', [PageController::class, 'adminCategories'])->name('categories');
    Route::get('/payments', [PageController::class, 'adminPayments'])->name('payments');
    Route::get('/settings', [PageController::class, 'adminSettings'])->name('settings');
});
