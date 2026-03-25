<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auto\RideController;
use Illuminate\Support\Facades\Route;

/**
 * Auto & Taxi API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/auto')->group(function () {
    // Driver search/listings
    Route::get('/drivers', [RideController::class, 'listDrivers'])
        ->name('api.auto.drivers.list');
    
    Route::get('/drivers/{driver}', [RideController::class, 'showDriver'])
        ->name('api.auto.drivers.show');
    
    // Pricing estimation
    Route::post('/rides/estimate', [RideController::class, 'estimatePrice'])
        ->name('api.auto.rides.estimate');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/auto')->group(function () {
    // Rides
    Route::post('/rides', [RideController::class, 'store'])
        ->name('api.auto.rides.store')
        ->middleware('throttle:50,1');
    
    Route::get('/rides/{ride}', [RideController::class, 'show'])
        ->name('api.auto.rides.show');
    
    Route::post('/rides/{ride}/complete', [RideController::class, 'complete'])
        ->name('api.auto.rides.complete')
        ->middleware('throttle:30,1');
    
    Route::post('/rides/{ride}/cancel', [RideController::class, 'cancel'])
        ->name('api.auto.rides.cancel')
        ->middleware('throttle:30,1');
    
    Route::get('/rides', [RideController::class, 'listUserRides'])
        ->name('api.auto.rides.list');
    
    // ========== Auto Service Orders ==========
    Route::apiResource('services/orders', AutoServiceOrderController::class);
    Route::post('services/orders/{order}/cancel', [AutoServiceOrderController::class, 'cancel'])->name('services.orders.cancel');
    Route::post('services/orders/{order}/complete', [AutoServiceOrderController::class, 'complete'])->name('services.orders.complete');
    Route::get('services', [AutoServiceOrderController::class, 'listServices'])->name('services.list');
    
    // ========== Car Wash Bookings ==========
    Route::apiResource('car-wash/bookings', CarWashBookingController::class);
    Route::post('car-wash/bookings/{booking}/cancel', [CarWashBookingController::class, 'cancel'])->name('car-wash.bookings.cancel');
    Route::get('car-wash/availability', [CarWashBookingController::class, 'availability'])->name('car-wash.availability');
    Route::get('car-wash/types', [CarWashBookingController::class, 'washTypes'])->name('car-wash.types');
    
    // ========== Auto Parts (Staff only) ==========
    Route::middleware('staff')->group(function () {
        Route::apiResource('parts', AutoPartController::class);
        Route::post('parts/{part}/restock', [AutoPartController::class, 'restock'])->name('parts.restock');
        Route::get('parts/low-stock', [AutoPartController::class, 'lowStock'])->name('parts.low-stock');
    });
});

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api'])->prefix('api/v1/auto')->group(function () {
    Route::get('taxi/drivers', [TaxiDriverController::class, 'list'])->name('taxi.drivers.list');
    Route::get('taxi/drivers/{driver}', [TaxiDriverController::class, 'show'])->name('taxi.drivers.show');
    Route::get('services', [AutoServiceOrderController::class, 'listServices'])->name('public.services.list');
    Route::get('car-wash/types', [CarWashBookingController::class, 'washTypes'])->name('public.car-wash.types');
});
