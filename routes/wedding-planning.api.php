<?php declare(strict_types=1);

use App\Domains\WeddingPlanning\Controllers\WeddingBookingController;
use Illuminate\Support\Facades\Route;

/**
 * Wedding Planning API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/wedding-planning')->group(function () {
    // List wedding bookings (with filters)
    Route::get('bookings', [WeddingBookingController::class, 'index'])
        ->name('wedding-planning.bookings.index');
    
    // Get booking details
    Route::get('bookings/{booking}', [WeddingBookingController::class, 'show'])
        ->name('wedding-planning.bookings.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/wedding-planning')->group(function () {
    // Create wedding booking
    Route::post('bookings', [WeddingBookingController::class, 'store'])
        ->name('wedding-planning.bookings.store')
        ->middleware('throttle:20,1');
    
    // Update booking
    Route::put('bookings/{booking}', [WeddingBookingController::class, 'update'])
        ->name('wedding-planning.bookings.update')
        ->middleware('throttle:30,1');
    
    // Cancel booking
    Route::delete('bookings/{booking}', [WeddingBookingController::class, 'destroy'])
        ->name('wedding-planning.bookings.destroy')
        ->middleware('throttle:20,1');
});
