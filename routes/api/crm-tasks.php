<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CatCRM\Infrastructure\Http\Controllers\Api\CRMTaskController;

/**
 * CRM Tasks & KPI API Routes
 * 
 * Production-ready routes для управления задачами и KPI в CRM.
 * Поддержка всех вертикалей и B2B/B2C контекста.
 */

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // ── Задачи ─────────────────────────────────────────────
    
    /**
     * Создать задачу
     * POST /api/crm/tasks
     */
    Route::post('/crm/tasks', [CRMTaskController::class, 'createTask']);
    
    /**
     * Получить задачи пользователя
     * GET /api/crm/tasks/user/{userId}
     */
    Route::get('/crm/tasks/user/{userId}', [CRMTaskController::class, 'getUserTasks']);
    
    /**
     * Завершить задачу
     * POST /api/crm/tasks/{taskId}/complete
     */
    Route::post('/crm/tasks/{taskId}/complete', [CRMTaskController::class, 'completeTask']);
    
    /**
     * Обновить прогресс задачи
     * POST /api/crm/tasks/{taskId}/progress
     */
    Route::post('/crm/tasks/{taskId}/progress', [CRMTaskController::class, 'updateTaskProgress']);
    
    /**
     * Получить просроченные задачи
     * GET /api/crm/tasks/overdue
     */
    Route::get('/crm/tasks/overdue', [CRMTaskController::class, 'getOverdueTasks']);
    
    /**
     * Получить задачи на сегодня
     * GET /api/crm/tasks/today
     */
    Route::get('/crm/tasks/today', [CRMTaskController::class, 'getTodayTasks']);
    
    // ── KPI Менеджеров ───────────────────────────────────
    
    /**
     * Создать KPI период для менеджера
     * POST /api/crm/kpi
     */
    Route::post('/crm/kpi', [CRMTaskController::class, 'createKPIPeriod']);
    
    /**
     * Получить KPI статистику менеджера
     * GET /api/crm/kpi/manager/{managerId}/stats
     */
    Route::get('/crm/kpi/manager/{managerId}/stats', [CRMTaskController::class, 'getManagerKPIStats']);
    
    /**
     * Получить активный KPI период менеджера
     * GET /api/crm/kpi/manager/{managerId}/active
     */
    Route::get('/crm/kpi/manager/{managerId}/active', [CRMTaskController::class, 'getActiveKPI']);
    
    /**
     * Завершить KPI период
     * POST /api/crm/kpi/{kpiId}/complete
     */
    Route::post('/crm/kpi/{kpiId}/complete', [CRMTaskController::class, 'completeKPIPeriod']);
});

/**
 * Rate limiting для CRM API
 */
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/api/crm/tasks', [CRMTaskController::class, 'createTask']);
    Route::post('/api/crm/kpi', [CRMTaskController::class, 'createKPIPeriod']);
});
