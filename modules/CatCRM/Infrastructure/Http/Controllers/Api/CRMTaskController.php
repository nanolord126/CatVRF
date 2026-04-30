<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use Modules\CatCRM\Application\Services\TaskService;
use Modules\CatCRM\Application\Services\ManagerKPIService;
use Modules\CatCRM\Domain\Entities\Task;
use Modules\CatCRM\Domain\Entities\ManagerKPI;
use Modules\CatCRM\Domain\Enums\TaskPriority;
use Modules\CatCRM\Domain\Enums\TaskStatus;
use Modules\CatCRM\Domain\Enums\TaskType;
use App\DTOs\CRM\CreateTaskDTO;
use App\DTOs\CRM\CreateManagerKPIDTO;

/**
 * CRMTaskController — API контроллер для задач и KPI в CRM
 * 
 * Production-ready controller с полной валидацией, обработкой ошибок
 * и поддержкой всех вертикалей и B2B/B2C контекста.
 */
final class CRMTaskController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly ManagerKPIService $kpiService
    ) {}

    /**
     * Создать задачу
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createTask(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
            'vertical_id' => 'nullable|integer|exists:verticals,id',
            'deal_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer|exists:users,id',
            'tender_id' => 'nullable|integer',
            'b2b_order_id' => 'nullable|integer',
            'assigned_to_id' => 'required|integer|exists:users,id',
            'created_by_id' => 'nullable|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'type' => 'nullable|string|in:call,email,meeting,follow_up,document,payment,delivery,custom',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|in:b2b,b2c,both',
            'kpi_weight' => 'nullable|numeric|min:0|max:10',
            'kpi_tracked' => 'nullable|boolean',
            'kpi_period_start' => 'nullable|date',
            'kpi_period_end' => 'nullable|date|after:kpi_period_start',
            'parent_task_id' => 'nullable|integer|exists:crm_tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $dto = CreateTaskDTO::fromArray($request->all());
            $task = $this->taskService->createTask($dto->toArray());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'uuid' => $task->uuid,
                    'title' => $task->title,
                    'status' => $task->status->value,
                    'priority' => $task->priority->value,
                    'assigned_to' => $task->assigned_to_id,
                    'due_date' => $task->due_date?->toDateTimeString(),
                    'kpi_tracked' => $task->kpi_tracked,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить задачи пользователя
     * 
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getUserTasks(Request $request, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'vertical_id' => 'nullable|integer|exists:verticals,id',
            'business_type' => 'nullable|string|in:b2b,b2c,both',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $tasks = Task::byTenant($tenantId)
                ->assignedTo($userId)
                ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
                ->when($request->input('priority'), fn ($q, $priority) => $q->where('priority', $priority))
                ->when($request->input('vertical_id'), fn ($q, $verticalId) => $q->where('vertical_id', $verticalId))
                ->when($request->input('business_type'), fn ($q, $businessType) => $q->where('business_type', $businessType))
                ->with(['deal', 'customer', 'assignedTo', 'vertical'])
                ->orderBy('due_date')
                ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $tasks->items(),
                'meta' => [
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Завершить задачу
     * 
     * @param int $taskId
     * @return JsonResponse
     */
    public function completeTask(int $taskId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            $updatedTask = $this->taskService->completeTask($task);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $updatedTask->id,
                    'status' => $updatedTask->status->value,
                    'completed_at' => $updatedTask->completed_at?->toDateTimeString(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Task not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to complete task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Создать KPI период для менеджера
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createKPIPeriod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
            'manager_id' => 'required|integer|exists:users,id',
            'vertical_id' => 'nullable|integer|exists:verticals,id',
            'period_type' => 'required|string|in:daily,weekly,monthly,quarterly,yearly',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'targets' => 'required|array',
            'targets.*.value' => 'required|numeric|min:0',
            'targets.*.weight' => 'required|numeric|min:0',
            'business_type' => 'nullable|string|in:b2b,b2c,both',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $dto = CreateManagerKPIDTO::fromArray($request->all());
            $kpi = $this->kpiService->createKPIPeriod($dto->toArray());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $kpi->id,
                    'uuid' => $kpi->uuid,
                    'manager_id' => $kpi->manager_id,
                    'period_type' => $kpi->period_type,
                    'period_start' => $kpi->period_start->toDateString(),
                    'period_end' => $kpi->period_end->toDateString(),
                    'targets' => $kpi->targets,
                    'score' => $kpi->score,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create KPI period: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить KPI статистику менеджера
     * 
     * @param Request $request
     * @param int $managerId
     * @return JsonResponse
     */
    public function getManagerKPIStats(Request $request, int $managerId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'period_type' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $periodType = $request->input('period_type', 'monthly');
            $stats = $this->kpiService->getManagerKPIStats($managerId, $tenantId, $periodType);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch KPI stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить активный KPI период менеджера
     * 
     * @param Request $request
     * @param int $managerId
     * @return JsonResponse
     */
    public function getActiveKPI(Request $request, int $managerId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $kpi = $this->kpiService->getActiveKPI($managerId, $tenantId);

            if (!$kpi) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active KPI period found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $kpi->id,
                    'uuid' => $kpi->uuid,
                    'manager_id' => $kpi->manager_id,
                    'period_type' => $kpi->period_type,
                    'period_start' => $kpi->period_start->toDateString(),
                    'period_end' => $kpi->period_end->toDateString(),
                    'targets' => $kpi->targets,
                    'actuals' => $kpi->actuals,
                    'score' => $kpi->score,
                    'status' => $kpi->status,
                    'tasks_assigned' => $kpi->tasks_assigned,
                    'tasks_completed' => $kpi->tasks_completed,
                    'tasks_on_time' => $kpi->tasks_on_time,
                    'tasks_overdue' => $kpi->tasks_overdue,
                    'completion_rate' => $kpi->getTaskCompletionRate(),
                    'on_time_rate' => $kpi->getOnTimeRate(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch active KPI: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Завершить KPI период
     * 
     * @param int $kpiId
     * @return JsonResponse
     */
    public function completeKPIPeriod(int $kpiId): JsonResponse
    {
        try {
            $kpi = ManagerKPI::findOrFail($kpiId);
            $completedKPI = $this->kpiService->completeKPIPeriod($kpi);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $completedKPI->id,
                    'status' => $completedKPI->status,
                    'score' => $completedKPI->score,
                    'tasks_completed' => $completedKPI->tasks_completed,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'KPI period not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to complete KPI period: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить просроченные задачи
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getOverdueTasks(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'vertical_id' => 'nullable|integer|exists:verticals,id',
            'business_type' => 'nullable|string|in:b2b,b2c,both',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $tasks = Task::byTenant($tenantId)
                ->overdue()
                ->when($request->input('vertical_id'), fn ($q, $verticalId) => $q->where('vertical_id', $verticalId))
                ->when($request->input('business_type'), fn ($q, $businessType) => $q->where('business_type', $businessType))
                ->with(['deal', 'customer', 'assignedTo', 'vertical'])
                ->orderBy('due_date')
                ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $tasks->items(),
                'meta' => [
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch overdue tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить задачи на сегодня
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTodayTasks(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'assigned_to_id' => 'nullable|integer|exists:users,id',
            'vertical_id' => 'nullable|integer|exists:verticals,id',
            'business_type' => 'nullable|string|in:b2b,b2c,both',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $tasks = Task::byTenant($tenantId)
                ->dueToday()
                ->when($request->input('assigned_to_id'), fn ($q, $assignedToId) => $q->where('assigned_to_id', $assignedToId))
                ->when($request->input('vertical_id'), fn ($q, $verticalId) => $q->where('vertical_id', $verticalId))
                ->when($request->input('business_type'), fn ($q, $businessType) => $q->where('business_type', $businessType))
                ->with(['deal', 'customer', 'assignedTo', 'vertical'])
                ->orderBy('due_date')
                ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $tasks->items(),
                'meta' => [
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch today tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить прогресс задачи
     * 
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function updateTaskProgress(Request $request, int $taskId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'progress' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $task = Task::findOrFail($taskId);
            $task->updateProgress($request->input('progress'));

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'progress' => $task->progress,
                    'status' => $task->status->value,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Task not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update task progress: ' . $e->getMessage(),
            ], 500);
        }
    }
}
