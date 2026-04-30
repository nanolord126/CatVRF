<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;

use App\Domains\CRM\DTOs\CreateTaskDto;
use App\Domains\CRM\Models\CrmTask;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * TaskService — сервис для управления задачами.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class TaskService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
    ) {}

    public function createTask(CreateTaskDto $dto): CrmTask
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_task_create',
            amount: 0,
            correlationId: $dto->correlationId
        );

        return $this->db->transaction(function () use ($dto): CrmTask {
            $task = CrmTask::query()->create($dto->toArray());

            $this->logger->info('CRM task created', [
                'task_id' => $task->id,
                'tenant_id' => $dto->tenantId,
                'type' => $dto->type,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->audit->log(
                'crm_task_created',
                CrmTask::class,
                $task->id,
                [],
                $dto->toArray(),
                $dto->correlationId
            );

            return $task;
        });
    }

    public function completeTask(int $taskId, ?string $correlationId = null): CrmTask
    {
        return $this->db->transaction(function () use ($taskId, $correlationId): CrmTask {
            $task = CrmTask::query()->findOrFail($taskId);
            $task->complete();

            $this->logger->info('CRM task completed', [
                'task_id' => $task->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_task_completed',
                CrmTask::class,
                $task->id,
                ['status' => 'pending'],
                ['status' => 'completed'],
                $correlationId
            );

            return $task->fresh();
        });
    }

    public function updateTask(int $taskId, array $data, ?string $correlationId = null): CrmTask
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'crm_task_update',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($taskId, $data, $correlationId): CrmTask {
            $task = CrmTask::query()->findOrFail($taskId);
            $oldValues = $task->toArray();

            $task->update($data);

            $this->logger->info('CRM task updated', [
                'task_id' => $task->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_task_updated',
                CrmTask::class,
                $task->id,
                $oldValues,
                $data,
                $correlationId
            );

            return $task->fresh();
        });
    }

    public function getTaskById(int $taskId, int $tenantId): CrmTask
    {
        return CrmTask::query()
            ->where('id', $taskId)
            ->where('tenant_id', $tenantId)
            ->with(['deal', 'customer', 'assignedTo', 'createdBy'])
            ->firstOrFail();
    }

    public function listTasks(
        int $tenantId,
        ?string $status = null,
        ?string $type = null,
        ?string $priority = null,
        ?int $assignedToId = null,
        ?int $dealId = null,
        ?int $customerId = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = CrmTask::query()->where('tenant_id', $tenantId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($priority !== null) {
            $query->where('priority', $priority);
        }

        if ($assignedToId !== null) {
            $query->where('assigned_to_id', $assignedToId);
        }

        if ($dealId !== null) {
            $query->where('deal_id', $dealId);
        }

        if ($customerId !== null) {
            $query->where('customer_id', $customerId);
        }

        return $query->with(['deal', 'customer', 'assignedTo'])
            ->orderBy('due_date')
            ->orderByDesc('priority')
            ->paginate($perPage);
    }

    public function getOverdueTasks(int $tenantId): Collection
    {
        return CrmTask::query()
            ->where('tenant_id', $tenantId)
            ->overdue()
            ->with(['deal', 'customer', 'assignedTo'])
            ->orderBy('due_date')
            ->get();
    }

    public function getTasksDueToday(int $tenantId): Collection
    {
        return CrmTask::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('due_date', now())
            ->where('status', '!=', 'completed')
            ->with(['deal', 'customer', 'assignedTo'])
            ->orderBy('due_date')
            ->get();
    }

    public function getTasksByDeal(int $dealId, int $tenantId): Collection
    {
        return CrmTask::query()
            ->where('tenant_id', $tenantId)
            ->where('deal_id', $dealId)
            ->with(['assignedTo'])
            ->orderBy('due_date')
            ->get();
    }

    public function createFollowUpTask(
        int $dealId,
        int $tenantId,
        ?int $customerId,
        ?int $assignedToId,
        string $dueDate,
        ?string $correlationId = null
    ): CrmTask {
        return $this->createTask(new CreateTaskDto(
            tenantId: $tenantId,
            businessGroupId: null,
            dealId: $dealId,
            customerId: $customerId,
            assignedToId: $assignedToId,
            createdById: $assignedToId,
            title: 'Follow up on deal',
            description: 'Follow up on the deal progress',
            type: 'follow_up',
            status: 'pending',
            priority: 'medium',
            dueDate: $dueDate,
            correlationId: $correlationId,
        ));
    }
}
