<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Http\Controllers\PropertyBookingController;
use Modules\RealEstate\Http\Controllers\PropertyController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('api/v1/real-estate')->group(function () {
    Route::prefix('bookings')->group(function () {
        Route::post('/', [PropertyBookingController::class, 'create']);
        Route::get('/', [PropertyBookingController::class, 'index']);
        Route::get('/{bookingId}', [PropertyBookingController::class, 'show']);
        Route::post('/{bookingId}/confirm', [PropertyBookingController::class, 'confirm']);
        Route::post('/{bookingId}/complete', [PropertyBookingController::class, 'complete']);
        Route::post('/{bookingId}/cancel', [PropertyBookingController::class, 'cancel']);
        Route::post('/{bookingId}/video-call', [PropertyBookingController::class, 'initiateVideoCall']);
    });

    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::get('/{id}', [PropertyController::class, 'show']);
        Route::post('/analyze-design', [PropertyController::class, 'analyzeDesign']);
        Route::get('/statistics', [PropertyController::class, 'getStatistics']);
    });

    Route::get('/properties/{propertyId}/available-slots', [PropertyBookingController::class, 'getAvailableSlots']);
});
