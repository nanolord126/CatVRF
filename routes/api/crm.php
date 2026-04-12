<?php

declare(strict_types=1);

use App\Domains\CRM\Http\Controllers\CrmAutomationController;
use App\Domains\CRM\Http\Controllers\CrmClientController;
use App\Domains\CRM\Http\Controllers\CrmSegmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CRM API Routes — /api/v1/crm
|--------------------------------------------------------------------------
|
| Роуты для CRM-модуля. Все защищены auth:sanctum + tenant middleware.
| correlation-id генерируется автоматически если не передан.
|
| Канон CatVRF 2026 — PRODUCTION MANDATORY.
|
*/

Route::prefix('v1/crm')
    ->middleware(['auth:sanctum', 'tenant'])
    ->group(function (): void {

        // ── Клиенты ──────────────────────────────────────────
        Route::get('clients', [CrmClientController::class, 'index'])
            ->name('crm.clients.index');

        Route::post('clients', [CrmClientController::class, 'store'])
            ->name('crm.clients.store');

        Route::get('clients/sleeping', [CrmClientController::class, 'sleeping'])
            ->name('crm.clients.sleeping');

        Route::get('clients/{id}', [CrmClientController::class, 'show'])
            ->name('crm.clients.show')
            ->whereNumber('id');

        Route::put('clients/{id}', [CrmClientController::class, 'update'])
            ->name('crm.clients.update')
            ->whereNumber('id');

        Route::get('clients/{id}/interactions', [CrmClientController::class, 'interactions'])
            ->name('crm.clients.interactions')
            ->whereNumber('id');

        Route::post('clients/{id}/interactions', [CrmClientController::class, 'storeInteraction'])
            ->name('crm.clients.interactions.store')
            ->whereNumber('id');

        // ── Сегменты ─────────────────────────────────────────
        Route::get('segments', [CrmSegmentController::class, 'index'])
            ->name('crm.segments.index');

        Route::post('segments', [CrmSegmentController::class, 'store'])
            ->name('crm.segments.store');

        Route::post('segments/{id}/recalculate', [CrmSegmentController::class, 'recalculate'])
            ->name('crm.segments.recalculate')
            ->whereNumber('id');

        // ── Автоматизации ────────────────────────────────────
        Route::get('automations', [CrmAutomationController::class, 'index'])
            ->name('crm.automations.index');

        Route::post('automations', [CrmAutomationController::class, 'store'])
            ->name('crm.automations.store');

        Route::post('automations/{id}/toggle', [CrmAutomationController::class, 'toggle'])
            ->name('crm.automations.toggle')
            ->whereNumber('id');

        // ── Аналитика ────────────────────────────────────────
        Route::get('analytics/dashboard', [CrmClientController::class, 'dashboard'])
            ->name('crm.analytics.dashboard');
    });
