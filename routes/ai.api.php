<?php

declare(strict_types=1);

use App\Domains\AI\Controllers\AIModelController;
use Illuminate\Support\Facades\Route;

/**
 * AI Vertical API Routes
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * Rate limiting: 100 requests per minute per IP
 * Authentication: Required for all routes
 * Tenant scoping: Automatic via middleware
 */

Route::middleware(['auth:sanctum', 'throttle:100,1'])->prefix('v1/ai')->group(function () {
    // AI Model Management
    Route::get('/models', [AIModelController::class, 'index'])
        ->name('v1.ai.models.index');

    Route::get('/models/{id}', [AIModelController::class, 'show'])
        ->name('v1.ai.models.show');

    Route::post('/models', [AIModelController::class, 'store'])
        ->name('v1.ai.models.store');

    Route::put('/models/{id}', [AIModelController::class, 'update'])
        ->name('v1.ai.models.update');

    Route::delete('/models/{id}', [AIModelController::class, 'destroy'])
        ->name('v1.ai.models.destroy');
});
