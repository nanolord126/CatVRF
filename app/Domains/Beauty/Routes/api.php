<?php

declare(strict_types=1);

/**
 * Beauty API Routes — CatVRF 2026.
 *
 * Загрузчик вертикальных маршрутов Beauty.
 * Подключает B2B и B2C роуты через отдельные файлы.
 *
 * B2B: /api/v1/beauty/b2b/* — Tenant Panel, auth:sanctum + tenant middleware.
 * B2C: /api/v1/beauty/* — публичная часть + auth:sanctum для записей.
 *
 * Отдельный api.php НЕ регистрирует роуты напрямую —
 * он служит точкой входа для ServiceProvider.
 */

use Illuminate\Support\Facades\Route;

/*
|----------------------------------------------------------------------
| Beauty B2C Routes (публичные + авторизованные)
|----------------------------------------------------------------------
*/
Route::prefix('beauty')
    ->group(base_path('app/Domains/Beauty/Routes/b2c.php'));

/*
|----------------------------------------------------------------------
| Beauty B2B Routes (только авторизованные, tenant-scoped)
|----------------------------------------------------------------------
*/
Route::prefix('beauty/b2b')
    ->middleware(['auth:sanctum', 'tenant'])
    ->group(base_path('app/Domains/Beauty/Routes/b2b.php'));
