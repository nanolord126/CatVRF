<?php declare(strict_types=1);

use App\Domains\CarRental\Controllers\RentalBookingController;
use Illuminate\Support\Facades\Route;

/**
 * Car Rental API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/car-rental')->group(function () {
    // List rental bookings (with filters)
    Route::get('bookings', [RentalBookingController::class, 'index'])
        ->name('car-rental.bookings.index');
    
    // Get booking details
    Route::get('bookings/{booking}', [RentalBookingController::class, 'show'])
        ->name('car-rental.bookings.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/car-rental')->group(function () {
    // Create rental booking
    Route::post('bookings', [RentalBookingController::class, 'store'])
        ->name('car-rental.bookings.store')
        ->middleware('throttle:20,1');
    
    // Update booking
    Route::put('bookings/{booking}', [RentalBookingController::class, 'update'])
        ->name('car-rental.bookings.update')
        ->middleware('throttle:30,1');
    
    // Cancel booking
    Route::delete('bookings/{booking}', [RentalBookingController::class, 'destroy'])
        ->name('car-rental.bookings.destroy')
        ->middleware('throttle:20,1');
});
