<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Beauty\AppointmentController;

/**
 * Beauty & Wellness API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/beauty')->group(function () {
    // ========== Beauty Salons (Public) ==========
    Route::get('salons', [\App\Domains\Beauty\Http\Controllers\SalonController::class, 'index'])
        ->name('beauty.salons.index');
    Route::get('salons/{salon}', [\App\Domains\Beauty\Http\Controllers\SalonController::class, 'show'])
        ->name('beauty.salons.show');
    Route::get('salons/{salon}/availability', [\App\Domains\Beauty\Http\Controllers\SalonController::class, 'availability'])
        ->name('beauty.salons.availability');

    // ========== Masters (Public) ==========
    Route::get('masters', [\App\Domains\Beauty\Http\Controllers\MasterController::class, 'index'])
        ->name('beauty.masters.index');
    Route::get('masters/{master}', [\App\Domains\Beauty\Http\Controllers\MasterController::class, 'show'])
        ->name('beauty.masters.show');
    Route::get('masters/{master}/portfolio', [\App\Domains\Beauty\Http\Controllers\MasterController::class, 'portfolio'])
        ->name('beauty.masters.portfolio');
    Route::get('masters/{master}/schedule', [\App\Domains\Beauty\Http\Controllers\MasterController::class, 'schedule'])
        ->name('beauty.masters.schedule');

    // ========== Services (Public) ==========
    Route::get('services', [\App\Domains\Beauty\Http\Controllers\ServiceController::class, 'index'])
        ->name('beauty.services.index');
    Route::get('services/{service}', [\App\Domains\Beauty\Http\Controllers\ServiceController::class, 'show'])
        ->name('beauty.services.show');

    // ========== Appointments (Auth) =====
    Route::middleware(['auth:sanctum', 'tenant'])->prefix('appointments')->group(function () {
        Route::post('/', [AppointmentController::class, 'store'])
            ->name('api.beauty.appointments.store')
            ->middleware('throttle:50,1');
        
        Route::get('/{appointment}', [AppointmentController::class, 'show'])
            ->name('api.beauty.appointments.show');
        
        Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel'])
            ->name('api.beauty.appointments.cancel')
            ->middleware('throttle:30,1');
        
        Route::post('/{appointment}/confirm', [AppointmentController::class, 'confirm'])
            ->name('api.beauty.appointments.confirm')
            ->middleware('throttle:30,1');
    });

    // ========== Reviews (Auth) ==========
    Route::middleware('auth')->group(function () {
        Route::post('reviews', [\App\Domains\Beauty\Http\Controllers\ReviewController::class, 'store'])
            ->name('beauty.reviews.store');
        Route::put('reviews/{review}', [\App\Domains\Beauty\Http\Controllers\ReviewController::class, 'update'])
            ->name('beauty.reviews.update');
        Route::delete('reviews/{review}', [\App\Domains\Beauty\Http\Controllers\ReviewController::class, 'destroy'])
            ->name('beauty.reviews.destroy');
    });

    // ========== Products (Public) ==========
    Route::get('products', [\App\Domains\Beauty\Http\Controllers\ProductController::class, 'index'])
        ->name('beauty.products.index');
    Route::get('products/{product}', [\App\Domains\Beauty\Http\Controllers\ProductController::class, 'show'])
        ->name('beauty.products.show');

    // ========== Management (Auth + Owner) ==========
    Route::middleware(['auth', 'beauty.owner'])->group(function () {
        Route::apiResource('salons', \App\Domains\Beauty\Http\Controllers\SalonController::class)
            ->except('index', 'show');
        Route::apiResource('masters', \App\Domains\Beauty\Http\Controllers\MasterController::class)
            ->except('index', 'show');
        Route::apiResource('services', \App\Domains\Beauty\Http\Controllers\ServiceController::class)
            ->except('index', 'show');
        Route::apiResource('products', \App\Domains\Beauty\Http\Controllers\ProductController::class)
            ->except('index', 'show');
        Route::apiResource('consumables', \App\Domains\Beauty\Http\Controllers\ConsumableController::class);
        
        // Consumable deduction logs
        Route::get('consumables/logs', [\App\Domains\Beauty\Http\Controllers\ConsumableController::class, 'logs'])
            ->name('beauty.consumables.logs');
    });
});
