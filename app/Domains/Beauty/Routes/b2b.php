<?php

declare(strict_types=1);

/**
 * Beauty B2B API Routes — CatVRF 2026.
 *
 * Prefix: /api/v1/beauty/b2b
 * Middleware: auth:sanctum, tenant
 * Tenant-scoped: все запросы фильтруются по tenant_id.
 *
 * @package CatVRF\Beauty
 * @version 2026.1
 */


use App\Domains\Beauty\Presentation\B2B\API\Controllers\AppointmentController;
use App\Domains\Beauty\Presentation\B2B\API\Controllers\MasterController;
use Illuminate\Support\Facades\Route;

/*
|----------------------------------------------------------------------
| Beauty B2B API Routes
| Prefix: /api/v1/beauty/b2b
| Middleware: auth:sanctum, tenant
|----------------------------------------------------------------------
*/

// Мастера (привязаны к конкретному салону через сессию тенанта)
Route::prefix('masters')->group(function (): void {
    Route::get('/', [MasterController::class, 'index'])
        ->name('beauty.b2b.masters.index');
    Route::post('/', [MasterController::class, 'store'])
        ->name('beauty.b2b.masters.store');
    Route::get('/{uuid}', [MasterController::class, 'show'])
        ->name('beauty.b2b.masters.show')
        ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
});

// Записи клиентов (CRUD + workflow: confirm / complete / cancel)
Route::prefix('appointments')->group(function (): void {
    Route::get('/', [AppointmentController::class, 'index'])
        ->name('beauty.b2b.appointments.index');
    Route::post('/', [AppointmentController::class, 'store'])
        ->name('beauty.b2b.appointments.store');
    Route::post('/{uuid}/confirm', [AppointmentController::class, 'confirm'])
        ->name('beauty.b2b.appointments.confirm')
        ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
    Route::post('/{uuid}/complete', [AppointmentController::class, 'complete'])
        ->name('beauty.b2b.appointments.complete')
        ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
    Route::post('/{uuid}/cancel', [AppointmentController::class, 'cancel'])
        ->name('beauty.b2b.appointments.cancel')
        ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
});
