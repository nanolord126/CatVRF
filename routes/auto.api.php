<?php declare(strict_types=1);

use App\Domains\Taxi\Http\Controllers\TaxiRideController;
use App\Domains\Taxi\Http\Controllers\TaxiDriverController;
use App\Domains\Auto\Http\Controllers\AutoServiceOrderController;
use App\Domains\Auto\Http\Controllers\CarWashBookingController;
use App\Domains\Auto\Http\Controllers\AutoPartController;
use Illuminate\Support\Facades\Route;

/**
 * Auto & Taxi API Routes
 * Production 2026.
 */

Route::middleware(['api', 'auth', 'tenant'])->prefix('api/auto')->group(function () {
    // ========== Taxi Rides ==========
    Route::apiResource('taxi/rides', TaxiRideController::class);

    // Custom ride actions
    Route::post('taxi/rides/{ride}/cancel', [TaxiRideController::class, 'cancel'])->name('taxi.rides.cancel');
    Route::post('taxi/rides/{ride}/rate', [TaxiRideController::class, 'rate'])->name('taxi.rides.rate');
    Route::get('taxi/rides/{ride}/status', [TaxiRideController::class, 'status'])->name('taxi.rides.status');

    // ========== Taxi Drivers ==========
    Route::apiResource('drivers', TaxiDriverController::class);

    // Driver location tracking
    Route::post('drivers/{driver}/location', [TaxiDriverController::class, 'updateLocation'])->name('drivers.update-location');
    Route::get('drivers/{driver}/location', [TaxiDriverController::class, 'getLocation'])->name('drivers.get-location');
    Route::post('drivers/{driver}/deactivate', [TaxiDriverController::class, 'deactivate'])->name('drivers.deactivate');
    Route::post('drivers/{driver}/activate', [TaxiDriverController::class, 'activate'])->name('drivers.activate');

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

// Public endpoints (no auth required)
Route::middleware('api')->prefix('api/auto/public')->group(function () {
    Route::get('taxi/drivers', [TaxiDriverController::class, 'list'])->name('taxi.drivers.list');
    Route::get('taxi/drivers/{driver}', [TaxiDriverController::class, 'show'])->name('taxi.drivers.show');

    Route::get('services', [AutoServiceOrderController::class, 'listServices'])->name('public.services.list');

    Route::get('car-wash/types', [CarWashBookingController::class, 'washTypes'])->name('public.car-wash.types');
});
