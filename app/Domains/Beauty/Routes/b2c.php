<?php

declare(strict_types=1);

/**
 * Beauty B2C API Routes — CatVRF 2026.
 *
 * Prefix: /api/v1/beauty
 * Middleware: auth:sanctum (только для записей).
 * Публичные маршруты: салоны, мастера, услуги.
 *
 * @package CatVRF\Beauty
 * @version 2026.1
 */


use App\Domains\Beauty\Presentation\B2C\API\Controllers\BookAppointmentController;
use App\Domains\Beauty\Presentation\B2C\API\Controllers\SalonDetailsController;
use Illuminate\Support\Facades\Route;

/*
|----------------------------------------------------------------------
| Beauty B2C API Routes
| Prefix: /api/v1/beauty
| Middleware: auth:sanctum (только для записи)
|----------------------------------------------------------------------
*/

// Публичный список салонов + поиск (без авторизации)
Route::prefix('salons')->group(function (): void {
    Route::get('/', [SalonDetailsController::class, 'index'])
        ->name('beauty.b2c.salons.index');
    Route::get('/{uuid}', [SalonDetailsController::class, 'show'])
        ->name('beauty.b2c.salons.show')
        ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
});

// Онлайн-запись клиента (требует авторизации)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/appointments', [BookAppointmentController::class, 'store'])
        ->name('beauty.b2c.appointments.store');
});
