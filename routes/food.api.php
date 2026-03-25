<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\Food\OrderController;
use Illuminate\Support\Facades\Route;

/**
 * Food & Delivery API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/food')->group(function () {
    // Restaurants
    Route::get('restaurants', [OrderController::class, 'listRestaurants'])
        ->name('api.food.restaurants.list');
    
    Route::get('restaurants/{restaurant}', [OrderController::class, 'showRestaurant'])
        ->name('api.food.restaurants.show');
    
    Route::get('restaurants/{restaurant}/menu', [OrderController::class, 'getMenu'])
        ->name('api.food.restaurants.menu');
    
    // Dishes
    Route::get('dishes', [OrderController::class, 'listDishes'])
        ->name('api.food.dishes.list');
    
    Route::get('dishes/{dish}', [OrderController::class, 'showDish'])
        ->name('api.food.dishes.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/food')->group(function () {
    // Orders
    Route::post('/orders', [OrderController::class, 'store'])
        ->name('api.food.orders.store')
        ->middleware('throttle:50,1');
    
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->name('api.food.orders.show');
    
    Route::post('/orders/{order}/ready', [OrderController::class, 'ready'])
        ->name('api.food.orders.ready')
        ->middleware('throttle:30,1');
    
    Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])
        ->name('api.food.orders.complete')
        ->middleware('throttle:30,1');
    
    Route::get('/orders', [OrderController::class, 'listUserOrders'])
        ->name('api.food.orders.list');
    
    // ========== Delivery Orders (Auth) ==========
    Route::get('deliveries', [DeliveryOrderController::class, 'index'])->name('deliveries.index');
    Route::get('deliveries/{delivery}', [DeliveryOrderController::class, 'show'])->name('deliveries.show');
    Route::post('deliveries/{delivery}/start', [DeliveryOrderController::class, 'start'])->name('deliveries.start');
    Route::get('deliveries/{delivery}/track', [DeliveryOrderController::class, 'track'])->name('deliveries.track');
});

// ===== STAFF ENDPOINTS (Auth + Staff) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'staff', 'throttle:100,1'])->prefix('api/v1/food')->group(function () {
    // ========== KDS (Kitchen Display System) ==========
    Route::get('kds/orders', [RestaurantOrderController::class, 'kdsOrders'])->name('kds.orders');
    Route::post('kds/orders/{order}/mark-ready', [RestaurantOrderController::class, 'markReady'])->name('kds.mark-ready');
    Route::post('kds/orders/{order}/mark-picked', [RestaurantOrderController::class, 'markPicked'])->name('kds.mark-picked');
    
    // ========== Management ==========
    Route::apiResource('dishes', DishController::class)->except('index', 'show');
    Route::apiResource('consumables', 'App\Domains\Food\Http\Controllers\ConsumableController');
});

// ===== ADMIN ENDPOINTS (Auth + Admin) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'admin', 'throttle:100,1'])->prefix('api/v1/food')->group(function () {
    // ========== Admin Panel ==========
    Route::apiResource('restaurants', RestaurantController::class)->except('index', 'show');
});
