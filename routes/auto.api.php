<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auto\RideController;
use App\Domains\Auto\Http\Controllers\AIDiagnosticsController;
use App\Domains\Auto\Http\Controllers\CarImportController;
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

    // ========== AI Diagnostics ==========
    Route::prefix('diagnostics')->group(function () {
        Route::post('/analyze', [AIDiagnosticsController::class, 'diagnose'])
            ->name('diagnostics.analyze')
            ->middleware('throttle:30,1');

        Route::post('/video-inspection/{vehicle}', [AIDiagnosticsController::class, 'initiateVideoInspection'])
            ->name('diagnostics.video-inspection')
            ->middleware('throttle:20,1');

        Route::post('/book-service/{vehicle}', [AIDiagnosticsController::class, 'bookService'])
            ->name('diagnostics.book-service')
            ->middleware('throttle:20,1');
    });

    // ========== Car Import ==========
    Route::prefix('import')->group(function () {
        Route::post('/calculate-duties', [CarImportController::class, 'calculateDuties'])
            ->name('import.calculate-duties')
            ->middleware('throttle:30,1');

        Route::post('/initiate', [CarImportController::class, 'initiateImport'])
            ->name('import.initiate')
            ->middleware('throttle:20,1');

        Route::post('/{import}/pay-duties', [CarImportController::class, 'payDuties'])
            ->name('import.pay-duties')
            ->middleware('throttle:20,1');

        Route::get('/{import}/status', [CarImportController::class, 'getImportStatus'])
            ->name('import.status')
            ->middleware('throttle:60,1');
    });
});
