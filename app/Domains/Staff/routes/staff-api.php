<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


use App\Domains\Staff\Presentation\Http\Controllers\Api\V1\StaffController;
use Illuminate\Support\Facades\Route;

/**
 * Staff API Routes — B2C публичные эндпоинты для работы с профилями сотрудников.
 *
 * Все маршруты загружаются через StaffServiceProvider::loadApiRoutes().
 * Middleware: api + auth:sanctum (задаётся в ServiceProvider).
 *
 * Prefix: /api/v1 (задаётся в ServiceProvider).
 */
Route::prefix('staff')
    ->name('staff.')
    ->group(static function (): void {

        /**
         * GET /api/v1/staff/{staffId}/profile
         *
         * Возвращает публичный профиль сотрудника.
         * Доступен для авторизованных пользователей (auth:sanctum).
         */
        Route::get(
            '{staffId}/profile',
            [StaffController::class, 'profile'],
        )->name('profile')
         ->whereUuid('staffId');

    });
