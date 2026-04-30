<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/component
 */


use Illuminate\Support\Facades\Route;
use App\Domains\Pet\Http\Controllers\B2BPetAppointmentController;
use App\Domains\Pet\Http\Controllers\PetAppointmentController;

/*
|--------------------------------------------------------------------------
| Pet API Routes
|--------------------------------------------------------------------------
| Канон CatVRF 2026:
| - correlation-id middleware обязателен
| - auth:sanctum + tenant scoping
| - rate-limit на все endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('pet')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {

        // B2C endpoints
        Route::prefix('v1')->group(function () {
            Route::get('/', [PetAppointmentController::class, 'index']);
            Route::post('/', [PetAppointmentController::class, 'store']);
            Route::get('/{id}', [PetAppointmentController::class, 'show']);
            Route::put('/{id}', [PetAppointmentController::class, 'update']);
            Route::delete('/{id}', [PetAppointmentController::class, 'destroy']);
        });

        // B2B endpoints
        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BPetAppointmentController::class, 'catalog']);
                Route::post('/bulk-order', [B2BPetAppointmentController::class, 'bulkOrder']);
            });
    });
