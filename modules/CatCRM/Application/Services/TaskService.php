<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\Task;
use Modules\CatCRM\Domain\Events\TaskCompleted;
use Modules\CatCRM\Domain\Events\TaskAssigned;
use Modules\CatCRM\Domain\Enums\TaskPriority;
use Modules\CatCRM\Domain\Enums\TaskStatus;
use Modules\CatCRM\Domain\Enums\TaskType;
use Illuminate\Support\Facades\DB;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * Task Service — Сервис для управления задачами в CRM
 */
final class TaskService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly ManagerKPIService $kpiService
    ) {}

    /**
     * Создать задачу
     */
    public function createTask(array $data): Task
    {
        $task = Task::create([
            'tenant_id' => $data['tenant_id'],
            'business_group_id' => $data['business_group_id'] ?? null,
            'vertical_id' => $data['vertical_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'tender_id' => $data['tender_id'] ?? null,
            'b2b_order_id' => $data['b2b_order_id'] ?? null,
            'assigned_to_id' => $data['assigned_to_id'] ?? null,
            'created_by_id' => $data['created_by_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'type' => $data['type'] ?? TaskType::Custom,
            'priority' => $data['priority'] ?? TaskPriority::Medium,
            'status' => $data['status'] ?? TaskStatus::Pending,
            'due_date' => $data['due_date'] ?? null,
            'location' => $data['location'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'kpi_weight' => $data['kpi_weight'] ?? 1.0,
            'kpi_tracked' => $data['kpi_tracked'] ?? false,
            'kpi_period_start' => $data['kpi_period_start'] ?? null,
            'kpi_period_end' => $data['kpi_period_end'] ?? null,
            'parent_task_id' => $data['parent_task_id'] ?? null,
            'correlation_id' => $this->correlationId,
        ]);

        if ($task->assigned_to_id) {
            TaskAssigned::dispatch($task);
        }

        return $task;
    }

    /**
     * Завершить задачу
     */
    public function completeTask(Task $task): Task
    {
        TaskCompleted::dispatch($task);
        $task->complete();
        $this->logAction('task_completed', 'Task', $task->id, [
            'title' => $task->title,
        ], $task->assigned_to_id, $task->tenant_id);
        return $task->fresh();
    }

    /**
     * Отменить задачу
     */
    public function cancelTask(Task $task): Task
    {
        $task->cancel();
        $this->logAction('task_cancelled', 'Task', $task->id, [
            'title' => $task->title,
        ], $task->assigned_to_id, $task->tenant_id);
        return $task->fresh();
    }

    /**
     * Начать выполнение задачи
     */
    public function startTask(Task $task): Task
    {
        $task->start();
        $this->logAction('task_started', 'Task', $task->id, [
            'title' => $task->title,
        ], $task->assigned_to_id, $task->tenant_id);
        return $task->fresh();
    }

    /**
     * Получить задачи пользователя
     */
    public function getUserTasks(int $userId, int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Task::byTenant($tenantId)
            ->assignedTo($userId)
            ->with(['deal', 'customer', 'assignedTo'])
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Получить просроченные задачи
     */
    public function getOverdueTasks(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Task::byTenant($tenantId)
            ->overdue()
            ->with(['deal', 'customer', 'assignedTo'])
            ->get();
    }

    /**
     * Получить задачи на сегодня
     */
    public function getTodayTasks(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Task::byTenant($tenantId)
            ->dueToday()
            ->with(['deal', 'customer', 'assignedTo'])
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Получить задачи с высоким приоритетом
     */
    public function getHighPriorityTasks(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Task::byTenant($tenantId)
            ->highPriority()
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])
            ->with(['deal', 'customer', 'assignedTo'])
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Создать напоминание о звонке
     */
    public function createCallReminder(array $data): Task
    {
        return $this->createTask([
            'tenant_id' => $data['tenant_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'assigned_to_id' => $data['assigned_to_id'],
            'title' => 'Звонок: ' . ($data['customer_name'] ?? 'Клиент'),
            'description' => $data['description'] ?? null,
            'type' => TaskType::Call,
            'priority' => $data['priority'] ?? TaskPriority::Medium,
            'due_date' => $data['due_date'],
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Создать напоминание о встрече
     */
    public function createMeetingReminder(array $data): Task
    {
        return $this->createTask([
            'tenant_id' => $data['tenant_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'assigned_to_id' => $data['assigned_to_id'],
            'title' => 'Встреча: ' . ($data['meeting_title'] ?? 'Встреча с клиентом'),
            'description' => $data['description'] ?? null,
            'type' => TaskType::Meeting,
            'priority' => $data['priority'] ?? TaskPriority::High,
            'due_date' => $data['due_date'],
            'location' => $data['location'] ?? null,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
