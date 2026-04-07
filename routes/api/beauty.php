<?php

declare(strict_types=1);

use App\Domains\Beauty\Http\Controllers\AIConstructorController;
use App\Domains\Beauty\Http\Controllers\AppointmentController;
use App\Domains\Beauty\Http\Controllers\MasterController;
use App\Domains\Beauty\Http\Controllers\SalonController;
use App\Domains\Beauty\Http\Controllers\ServiceController;
use App\Domains\Beauty\Http\Controllers\SlotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Beauty B2C API Routes
|--------------------------------------------------------------------------
|
| Публичное API вертикали Beauty.
|
| Middleware pipeline:
|   correlation-id → tenant → rate-limit → controller
|   (auth:sanctum для авторизованных эндпоинтов)
|
| Prefix: /api/beauty
|
*/

Route::prefix('api/beauty')
    ->middleware(['correlation-id', 'tenant', 'throttle:120,1'])
    ->group(function (): void {

        /*
        |--------------------------------------------------------------
        | Публичные (без авторизации) — каталог
        |--------------------------------------------------------------
        */

        // Салоны: список + детали
        Route::get('salons', [SalonController::class, 'index'])
            ->name('beauty.salons.index');

        Route::get('salons/{salon}', [SalonController::class, 'show'])
            ->name('beauty.salons.show')
            ->whereNumber('salon');

        // Мастера конкретного салона
        Route::get('salons/{salon}/masters', [MasterController::class, 'index'])
            ->name('beauty.masters.index')
            ->whereNumber('salon');

        // Детали мастера
        Route::get('masters/{master}', [MasterController::class, 'show'])
            ->name('beauty.masters.show')
            ->whereNumber('master');

        // Услуги конкретного салона
        Route::get('salons/{salon}/services', [ServiceController::class, 'index'])
            ->name('beauty.services.index')
            ->whereNumber('salon');

        // Детали услуги
        Route::get('services/{service}', [ServiceController::class, 'show'])
            ->name('beauty.services.show')
            ->whereNumber('service');

        // Свободные слоты мастера
        Route::get('masters/{master}/slots', [SlotController::class, 'index'])
            ->name('beauty.slots.index')
            ->whereNumber('master');

        /*
        |--------------------------------------------------------------
        | Авторизованные (auth:sanctum) — бронирование и AI
        |--------------------------------------------------------------
        */

        Route::middleware(['auth:sanctum', 'fraud-check'])
            ->group(function (): void {

                // --- Записи ---
                Route::get('appointments', [AppointmentController::class, 'index'])
                    ->name('beauty.appointments.index');

                Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])
                    ->name('beauty.appointments.show')
                    ->whereNumber('appointment');

                Route::post('appointments', [AppointmentController::class, 'store'])
                    ->name('beauty.appointments.store');

                Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
                    ->name('beauty.appointments.cancel')
                    ->whereNumber('appointment');

                // --- Слоты: резерв / отмена ---
                Route::post('slots/{slot}/reserve', [SlotController::class, 'reserve'])
                    ->name('beauty.slots.reserve')
                    ->whereNumber('slot');

                Route::post('slots/{slot}/release', [SlotController::class, 'release'])
                    ->name('beauty.slots.release')
                    ->whereNumber('slot');

                // --- AI-конструктор ---
                Route::post('ai/analyze', [AIConstructorController::class, 'analyze'])
                    ->name('beauty.ai.analyze')
                    ->middleware('throttle:10,1'); // max 10 AI-запросов в минуту

                Route::get('ai/designs', [AIConstructorController::class, 'designs'])
                    ->name('beauty.ai.designs');

                // --- Генерация слотов (Tenant / автоматика) ---
                Route::post('masters/{master}/slots/generate', [SlotController::class, 'generate'])
                    ->name('beauty.slots.generate')
                    ->whereNumber('master');
            });
    });
