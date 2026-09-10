<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

// ---------- عام (بدون تسجيل دخول) ----------
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']); // Guest Checkout
Route::get('/payments/settings', [PaymentController::class, 'settings']);
Route::get('/settings', [SettingController::class, 'public']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/health', fn () => response()->json(['success' => true, 'status' => 'ok']));

// ---------- تتطلب تسجيل دخول (Sanctum) ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // ---------- admin أو staff ----------
    Route::middleware('role:admin,staff')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::post('/products/{id}/images', [ProductController::class, 'addImage']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);

        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::put('/payments/{id}/status', [PaymentController::class, 'updateStatus']);

        Route::get('/contact', [ContactController::class, 'index']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    });

    // ---------- admin فقط ----------
    Route::middleware('role:admin')->group(function () {
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        Route::get('/settings/all', [SettingController::class, 'all']);
        Route::put('/settings', [SettingController::class, 'update']);
    });
});
