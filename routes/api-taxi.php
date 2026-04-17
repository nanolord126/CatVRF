<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Taxi\Http\Controllers\TaxiRideController;

/**
 * API Routes for Taxi Vertical — Production Ready 2026.
 * 
 * All routes require authentication and tenant context.
 * Follows CatVRF 2026 canon: correlation_id header, rate limiting.
 */

Route::middleware(['auth:api', 'tenant'])->prefix('taxi')->group(function () {
    
    // Ride management
    Route::post('/rides', [TaxiRideController::class, 'create'])
        ->name('taxi.rides.create');
    
    Route::get('/rides/{id}', [TaxiRideController::class, 'show'])
        ->name('taxi.rides.show');
    
    Route::post('/rides/{id}/match-driver', [TaxiRideController::class, 'matchDriver'])
        ->name('taxi.rides.match-driver');
    
    Route::post('/rides/{id}/start', [TaxiRideController::class, 'startRide'])
        ->name('taxi.rides.start');
    
    Route::post('/rides/{id}/complete', [TaxiRideController::class, 'completeRide'])
        ->name('taxi.rides.complete');
    
    Route::post('/rides/{id}/cancel', [TaxiRideController::class, 'cancelRide'])
        ->name('taxi.rides.cancel');
    
    Route::post('/rides/{id}/rate', [TaxiRideController::class, 'submitRating'])
        ->name('taxi.rides.rate');
    
    // Driver location updates
    Route::post('/drivers/{driverId}/location', [TaxiRideController::class, 'updateDriverLocation'])
        ->name('taxi.drivers.update-location');
    
    // AI-powered features
    Route::post('/ai/analyze-route', [TaxiRideController::class, 'analyzeRoute'])
        ->name('taxi.ai.analyze-route');
    
    Route::post('/ai/predict-surge', [TaxiRideController::class, 'predictSurge'])
        ->name('taxi.ai.predict-surge');
    
    Route::get('/ai/drivers/{driverId}/analyze', [TaxiRideController::class, 'analyzeDriver'])
        ->name('taxi.ai.analyze-driver');
});
