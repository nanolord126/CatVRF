<?php

declare(strict_types=1);

use App\Domains\Beauty\Controllers\BeautyLoyaltyController;
use App\Domains\Beauty\Controllers\BeautyFraudDetectionController;
use App\Domains\Beauty\Controllers\DynamicPricingController;
use App\Domains\Beauty\Controllers\MasterMatchingController;
use App\Domains\Beauty\Controllers\VideoCallController;
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

        // Публичный тестовый эндпоинт для нагрузочного тестирования
        Route::post('test/stress', function () {
            return response()->json([
                'success' => true,
                'message' => 'Test endpoint',
                'timestamp' => now(),
            ]);
        })->name('beauty.test.stress');

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

                // --- AI-подбор мастера по фото ---
                Route::post('masters/match-by-photo', [MasterMatchingController::class, 'matchByPhoto'])
                    ->name('beauty.masters.match-by-photo')
                    ->middleware('throttle:20,1'); // max 20 запросов в минуту

                Route::get('masters/match-history', [MasterMatchingController::class, 'getMatchHistory'])
                    ->name('beauty.masters.match-history');

                // --- Dynamic pricing (AI-driven) ---
                Route::post('pricing/calculate', [DynamicPricingController::class, 'calculate'])
                    ->name('beauty.pricing.calculate')
                    ->middleware('throttle:30,1'); // max 30 запросов в минуту

                Route::get('pricing/history', [DynamicPricingController::class, 'getPriceHistory'])
                    ->name('beauty.pricing.history');

                // --- Video calls (WebRTC) ---
                Route::post('video-calls/initiate', [VideoCallController::class, 'initiate'])
                    ->name('beauty.video-calls.initiate')
                    ->middleware('throttle:10,1'); // max 10 calls в минуту

                Route::post('video-calls/end', [VideoCallController::class, 'end'])
                    ->name('beauty.video-calls.end');

                // --- Loyalty & Gamification ---
                Route::post('loyalty/action', [BeautyLoyaltyController::class, 'processAction'])
                    ->name('beauty.loyalty.action')
                    ->middleware('throttle:60,1'); // max 60 actions в минуту

                Route::get('loyalty/status', [BeautyLoyaltyController::class, 'getStatus'])
                    ->name('beauty.loyalty.status');

                Route::post('loyalty/referral/generate', [BeautyLoyaltyController::class, 'generateReferral'])
                    ->name('beauty.loyalty.referral.generate')
                    ->middleware('throttle:5,1'); // max 5 генераций в минуту

                // --- Fraud Detection (AI-powered) ---
                Route::post('fraud/analyze', [BeautyFraudDetectionController::class, 'analyze'])
                    ->name('beauty.fraud.analyze')
                    ->middleware('throttle:100,1'); // max 100 analyses в минуту

                Route::post('fraud/suspicious-ip', [BeautyFraudDetectionController::class, 'addSuspiciousIP'])
                    ->name('beauty.fraud.suspicious-ip');

                Route::post('fraud/failed-payment', [BeautyFraudDetectionController::class, 'recordFailedPayment'])
                    ->name('beauty.fraud.failed-payment');

                // --- Генерация слотов (Tenant / автоматика) ---
                Route::post('masters/{master}/slots/generate', [SlotController::class, 'generate'])
                    ->name('beauty.slots.generate')
                    ->whereNumber('master');
            });
    });
