<?php declare(strict_types=1);

use App\Domains\Food\Http\Controllers\RestaurantController;
use App\Domains\Food\Http\Controllers\RestaurantOrderController;
use App\Domains\Food\Http\Controllers\DeliveryOrderController;
use App\Domains\Food\Http\Controllers\DishController;
use Illuminate\Support\Facades\Route;

/**
 * Food & Delivery API Routes
 * Production 2026.
 */

Route::middleware(['api', 'tenant'])->prefix('api/food')->group(function () {
    // ========== Restaurants (Public) ==========
    Route::get('restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');
    Route::get('restaurants/{restaurant}', [RestaurantController::class, 'show'])->name('restaurants.show');
    Route::get('restaurants/{restaurant}/menu', [RestaurantController::class, 'getMenu'])->name('restaurants.menu');

    // ========== Dishes (Menu) ==========
    Route::get('dishes', [DishController::class, 'index'])->name('dishes.index');
    Route::get('dishes/{dish}', [DishController::class, 'show'])->name('dishes.show');

    // ========== Restaurant Orders (Auth) ==========
    Route::middleware('auth')->group(function () {
        Route::apiResource('orders', RestaurantOrderController::class);
        Route::post('orders/{order}/cancel', [RestaurantOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/confirm-payment', [RestaurantOrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
        Route::get('orders/{order}/status', [RestaurantOrderController::class, 'status'])->name('orders.status');
    });

    // ========== Delivery Orders (Auth) ==========
    Route::middleware('auth')->group(function () {
        Route::get('deliveries', [DeliveryOrderController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{delivery}', [DeliveryOrderController::class, 'show'])->name('deliveries.show');
        Route::post('deliveries/{delivery}/start', [DeliveryOrderController::class, 'start'])->name('deliveries.start');
        Route::get('deliveries/{delivery}/track', [DeliveryOrderController::class, 'track'])->name('deliveries.track');
    });

    // ========== KDS (Kitchen Display System - Staff Only) ==========
    Route::middleware(['auth', 'staff'])->group(function () {
        Route::get('kds/orders', [RestaurantOrderController::class, 'kdsOrders'])->name('kds.orders');
        Route::post('kds/orders/{order}/mark-ready', [RestaurantOrderController::class, 'markReady'])->name('kds.mark-ready');
        Route::post('kds/orders/{order}/mark-picked', [RestaurantOrderController::class, 'markPicked'])->name('kds.mark-picked');
    });

    // ========== Management (Staff/Admin) ==========
    Route::middleware(['auth', 'staff'])->group(function () {
        Route::apiResource('dishes', DishController::class)->except('index', 'show');
        Route::apiResource('consumables', 'App\Domains\Food\Http\Controllers\ConsumableController');
    });

    // ========== Admin Panel (Admin Only) ==========
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::apiResource('restaurants', RestaurantController::class)->except('index', 'show');
    });
});
