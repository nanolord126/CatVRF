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

        // ── Tasks (Задачи CRM) ─────────────────────────────
        Route::prefix('tasks')->group(function () {
            Route::get('/my-tasks', function (Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $userId = $request->user()->id;
                $tenantId = $request->user()->tenant_id;
                $tasks = $taskService->getUserTasks($userId, $tenantId);
                return response()->json($tasks);
            })->name('crm.tasks.my-tasks');

            Route::get('/overdue', function (Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $tenantId = $request->user()->tenant_id;
                $tasks = $taskService->getOverdueTasks($tenantId);
                return response()->json($tasks);
            })->name('crm.tasks.overdue');

            Route::get('/today', function (Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $tenantId = $request->user()->tenant_id;
                $tasks = $taskService->getTodayTasks($tenantId);
                return response()->json($tasks);
            })->name('crm.tasks.today');

            Route::post('/', function (Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $request->validate([
                    'title' => 'required|string|max:255',
                    'tenant_id' => 'required|exists:tenants,id',
                    'assigned_to_id' => 'nullable|exists:users,id',
                    'supplier_id' => 'nullable|exists:users,id',
                    'tender_id' => 'nullable|exists:tenders,id',
                    'b2b_order_id' => 'nullable|exists:b2b_orders,id',
                    'vertical_id' => 'nullable|exists:verticals,id',
                    'type' => 'string',
                    'priority' => 'string',
                    'due_date' => 'nullable|date',
                    'business_type' => 'in:b2b,b2c,both',
                    'kpi_tracked' => 'boolean',
                    'kpi_weight' => 'numeric|min:0|max:10',
                ]);
                $task = $taskService->createTask(array_merge($request->all(), [
                    'created_by_id' => $request->user()->id,
                ]));
                return response()->json($task, 201);
            })->name('crm.tasks.create');

            Route::post('/supplier', function (Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $request->validate([
                    'supplier_id' => 'required|exists:users,id',
                    'title' => 'required|string|max:255',
                    'tenant_id' => 'required|exists:tenants,id',
                    'assigned_to_id' => 'required|exists:users,id',
                    'vertical_id' => 'nullable|exists:verticals,id',
                    'tender_id' => 'nullable|exists:tenders,id',
                    'b2b_order_id' => 'nullable|exists:b2b_orders,id',
                    'due_date' => 'nullable|date',
                ]);
                $task = $taskService->createSupplierTask(array_merge($request->all(), [
                    'created_by_id' => $request->user()->id,
                ]));
                return response()->json($task, 201);
            })->name('crm.tasks.supplier.create');

            Route::post('/{taskId}/complete', function (int $taskId, Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $task = \Modules\CatCRM\Domain\Entities\Task::findOrFail($taskId);
                $task = $taskService->completeTask($task);
                return response()->json($task);
            })->name('crm.tasks.complete')->whereNumber('taskId');

            Route::post('/{taskId}/progress', function (int $taskId, Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $request->validate(['progress' => 'required|integer|min:0|max:100']);
                $task = \Modules\CatCRM\Domain\Entities\Task::findOrFail($taskId);
                $task = $taskService->updateTaskProgress($task, $request->progress);
                return response()->json($task);
            })->name('crm.tasks.progress')->whereNumber('taskId');

            Route::post('/{taskId}/cancel', function (int $taskId, Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $task = \Modules\CatCRM\Domain\Entities\Task::findOrFail($taskId);
                $task = $taskService->cancelTask($task);
                return response()->json($task);
            })->name('crm.tasks.cancel')->whereNumber('taskId');

            Route::get('/supplier/{supplierId}', function (int $supplierId, Illuminate\Http\Request $request, \Modules\CatCRM\Application\Services\TaskService $taskService) {
                $tenantId = $request->user()->tenant_id;
                $verticalId = $request->query('vertical_id');
                $tasks = $taskService->getSupplierTasks($supplierId, $tenantId, $verticalId);
                return response()->json($tasks);
            })->name('crm.tasks.supplier.list')->whereNumber('supplierId');
        });

        // ── Manager KPIs ──────────────────────────────────────
        Route::prefix('manager/kpi')->group(function () {
            Route::get('/', function (Illuminate\Http\Request $request, \App\Services\ManagerKPIService $kpiService) {
                $managerId = $request->user()->id;
                $periodStart = $request->query('period_start');
                $periodEnd = $request->query('period_end');
                $kpis = $kpiService->getManagerKPIs($managerId, $periodStart, $periodEnd);
                return response()->json($kpis);
            })->name('crm.manager.kpi.list');

            Route::get('/statistics', function (Illuminate\Http\Request $request, \App\Services\ManagerKPIService $kpiService) {
                $managerId = $request->user()->id;
                $tenantId = $request->user()->tenant_id;
                $stats = $kpiService->getManagerStatistics($managerId, $tenantId);
                return response()->json($stats);
            })->name('crm.manager.kpi.statistics');

            Route::post('/', function (Illuminate\Http\Request $request, \App\Services\ManagerKPIService $kpiService) {
                $request->validate([
                    'manager_id' => 'required|exists:users,id',
                    'tenant_id' => 'required|exists:tenants,id',
                    'vertical_id' => 'nullable|exists:verticals,id',
                    'kpi_type' => 'required|string|in:sales_revenue,sales_count,conversion_rate,task_completion,client_retention,new_clients,bonus_earned',
                    'kpi_name' => 'required|string|max:255',
                    'target_value' => 'required|numeric',
                    'target_unit' => 'required|string',
                    'minimum_value' => 'nullable|numeric',
                    'period_type' => 'required|in:daily,weekly,monthly,quarterly,yearly',
                    'period_start' => 'required|date',
                    'period_end' => 'required|date',
                    'bonus_eligible' => 'boolean',
                    'bonus_multiplier' => 'numeric|min:0',
                    'bonus_amount' => 'nullable|numeric',
                ]);
                $kpi = $kpiService->createKPI($request->all());
                return response()->json($kpi, 201);
            })->name('crm.manager.kpi.create');

            Route::post('/{kpiId}/update', function (int $kpiId, Illuminate\Http\Request $request, \App\Services\ManagerKPIService $kpiService) {
                $request->validate([
                    'new_value' => 'required|numeric',
                    'event_type' => 'required|string',
                    'reference_type' => 'nullable|string',
                    'reference_id' => 'nullable|integer',
                ]);
                $kpi = $kpiService->updateKPIValue(
                    $kpiId,
                    $request->new_value,
                    $request->event_type,
                    $request->reference_type,
                    $request->reference_id
                );
                return response()->json($kpi);
            })->name('crm.manager.kpi.update')->whereNumber('kpiId');

            Route::post('/{kpiId}/process-bonus', function (int $kpiId, Illuminate\Http\Request $request, \App\Services\ManagerKPIService $kpiService) {
                $kpiService->processKPIAchievementBonuses($kpiId, $request->user()->id);
                return response()->json(['message' => 'Bonus processed']);
            })->name('crm.manager.kpi.process-bonus')->whereNumber('kpiId');

            Route::post('/auto-create/{managerId}', function (int $managerId, Illuminate\Http\Request $request, \App\Services\ManagerKPIService $kpiService) {
                $tenantId = $request->user()->tenant_id;
                $verticalId = $request->query('vertical_id');
                $kpiService->autoCreateKPIsForManager($managerId, $tenantId, $verticalId);
                return response()->json(['message' => 'KPIs created']);
            })->name('crm.manager.kpi.auto-create')->whereNumber('managerId');
        });
    });
