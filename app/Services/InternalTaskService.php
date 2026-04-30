<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InternalTask;
use App\Models\TaskAssignment;
use App\Models\TaskCheckpoint;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

final readonly class InternalTaskService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create a new internal task
     */
    public function createTask(array $data, string $correlationId = ''): InternalTask
    {
        return $this->db->transaction(function () use ($data, $correlationId) {
            $task = InternalTask::create([
                'tenant_id' => $data['tenant_id'],
                'creator_id' => $data['creator_id'],
                'assignee_id' => $data['assignee_id'] ?? null,
                'controller_id' => $data['controller_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? InternalTask::PRIORITY_MEDIUM,
                'status' => InternalTask::STATUS_PENDING,
                'type' => $data['type'] ?? InternalTask::TYPE_NOTIFICATION,
                'due_date' => $data['due_date'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Create assignments
            if (isset($data['controller_id']) && $data['controller_id']) {
                TaskAssignment::create([
                    'task_id' => $task->id,
                    'user_id' => $data['controller_id'],
                    'role' => TaskAssignment::ROLE_CONTROLLER,
                    'assigned_at' => now(),
                ]);
            }

            if (isset($data['assignee_id']) && $data['assignee_id']) {
                TaskAssignment::create([
                    'task_id' => $task->id,
                    'user_id' => $data['assignee_id'],
                    'role' => TaskAssignment::ROLE_ASSIGNEE,
                    'assigned_at' => now(),
                ]);
            }

            // Create checkpoints if provided
            if (isset($data['checkpoints']) && is_array($data['checkpoints'])) {
                foreach ($data['checkpoints'] as $index => $checkpoint) {
                    TaskCheckpoint::create([
                        'task_id' => $task->id,
                        'title' => $checkpoint['title'],
                        'description' => $checkpoint['description'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }

            // Invalidate cache
            $this->invalidateTaskCache($data['tenant_id']);

            // Log audit
            $this->logAction(
                'internal_task_created',
                'InternalTask',
                $task->id,
                [
                    'tenant_id' => $data['tenant_id'],
                    'title' => $data['title'],
                    'type' => $task->type,
                    'priority' => $task->priority,
                ],
                $correlationId
            );

            $this->logger->channel('tasks')->info('Internal task created', [
                'task_id' => $task->id,
                'tenant_id' => $data['tenant_id'],
                'correlation_id' => $correlationId,
            ]);

            return $task;
        });
    }

    /**
     * Update task status
     */
    public function updateStatus(int $taskId, string $status, ?int $userId = null, string $correlationId = ''): InternalTask
    {
        $task = InternalTask::findOrFail($taskId);

        $this->db->transaction(function () use ($task, $status, $userId, $correlationId) {
            $task->update(['status' => $status]);

            if ($status === InternalTask::STATUS_COMPLETED) {
                $task->update(['completed_at' => now()]);
            }

            // Invalidate cache
            $this->invalidateTaskCache($task->tenant_id);

            $this->logAction(
                'internal_task_status_updated',
                'InternalTask',
                $task->id,
                [
                    'old_status' => $task->getOriginal('status'),
                    'new_status' => $status,
                    'user_id' => $userId,
                ],
                $correlationId
            );
        });

        return $task->fresh();
    }

    /**
     * Add checkpoint to task
     */
    public function addCheckpoint(int $taskId, array $checkpointData, string $correlationId = ''): TaskCheckpoint
    {
        $task = InternalTask::findOrFail($taskId);

        $checkpoint = TaskCheckpoint::create([
            'task_id' => $taskId,
            'title' => $checkpointData['title'],
            'description' => $checkpointData['description'] ?? null,
            'sort_order' => $checkpointData['sort_order'] ?? $task->checkpoints->count(),
        ]);

        $this->logAction(
            'task_checkpoint_added',
            'TaskCheckpoint',
            $checkpoint->id,
            ['task_id' => $taskId],
            $correlationId
        );

        return $checkpoint;
    }

    /**
     * Complete checkpoint
     */
    public function completeCheckpoint(int $checkpointId, int $userId, string $correlationId = ''): TaskCheckpoint
    {
        $checkpoint = TaskCheckpoint::findOrFail($checkpointId);
        $checkpoint->markAsCompleted($userId);

        $this->logAction(
            'task_checkpoint_completed',
            'TaskCheckpoint',
            $checkpoint->id,
            ['completed_by' => $userId],
            $correlationId
        );

        // Check if all checkpoints are completed
        $task = $checkpoint->task;
        if ($task->checkpoints->every(fn ($cp) => $cp->is_completed)) {
            $this->updateStatus($task->id, InternalTask::STATUS_REVIEW, $userId, $correlationId);
        }

        return $checkpoint->fresh();
    }

    /**
     * Get overdue tasks for controller
     */
    public function getOverdueTasksForController(int $controllerId, int $tenantId): array
    {
        $cacheKey = "tasks:overdue:controller:{$controllerId}:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($controllerId, $tenantId) {
            return InternalTask::where('tenant_id', $tenantId)
                ->where('controller_id', $controllerId)
                ->where('due_date', '<', now())
                ->whereNotIn('status', [InternalTask::STATUS_COMPLETED, InternalTask::STATUS_CANCELLED])
                ->with(['assignee', 'checkpoints'])
                ->orderBy('due_date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get tasks due soon
     */
    public function getTasksDueSoon(int $tenantId, int $days = 3): array
    {
        $cacheKey = "tasks:due_soon:{$tenantId}:{$days}";

        return $this->cache->remember($cacheKey, now()->addMinutes(30), function () use ($tenantId, $days) {
            return InternalTask::where('tenant_id', $tenantId)
                ->whereBetween('due_date', [now(), now()->addDays($days)])
                ->whereNotIn('status', [InternalTask::STATUS_COMPLETED, InternalTask::STATUS_CANCELLED])
                ->with(['assignee', 'controller', 'checkpoints'])
                ->orderBy('due_date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get task statistics
     */
    public function getTaskStatistics(int $tenantId): array
    {
        $cacheKey = "tasks:statistics:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($tenantId) {
            $tasks = InternalTask::where('tenant_id', $tenantId);

            return [
                'total' => $tasks->count(),
                'pending' => (clone $tasks)->where('status', InternalTask::STATUS_PENDING)->count(),
                'in_progress' => (clone $tasks)->where('status', InternalTask::STATUS_IN_PROGRESS)->count(),
                'review' => (clone $tasks)->where('status', InternalTask::STATUS_REVIEW)->count(),
                'completed' => (clone $tasks)->where('status', InternalTask::STATUS_COMPLETED)->count(),
                'overdue' => (clone $tasks)->overdue()->count(),
                'due_soon' => (clone $tasks)->dueSoon()->count(),
                'by_priority' => [
                    'urgent' => (clone $tasks)->where('priority', InternalTask::PRIORITY_URGENT)->count(),
                    'high' => (clone $tasks)->where('priority', InternalTask::PRIORITY_HIGH)->count(),
                    'medium' => (clone $tasks)->where('priority', InternalTask::PRIORITY_MEDIUM)->count(),
                    'low' => (clone $tasks)->where('priority', InternalTask::PRIORITY_LOW)->count(),
                ],
                'by_type' => [
                    'notification' => (clone $tasks)->where('type', InternalTask::TYPE_NOTIFICATION)->count(),
                    'campaign' => (clone $tasks)->where('type', InternalTask::TYPE_CAMPAIGN)->count(),
                    'report' => (clone $tasks)->where('type', InternalTask::TYPE_REPORT)->count(),
                    'review' => (clone $tasks)->where('type', InternalTask::TYPE_REVIEW)->count(),
                ],
            ];
        });
    }

    /**
     * Assign task to user
     */
    public function assignTask(int $taskId, int $userId, string $role = TaskAssignment::ROLE_ASSIGNEE, string $correlationId = ''): TaskAssignment
    {
        $task = InternalTask::findOrFail($taskId);

        $assignment = TaskAssignment::updateOrCreate(
            [
                'task_id' => $taskId,
                'user_id' => $userId,
                'role' => $role,
            ],
            [
                'assigned_at' => now(),
            ]
        );

        // Update task if assigning as primary assignee
        if ($role === TaskAssignment::ROLE_ASSIGNEE) {
            $task->update(['assignee_id' => $userId]);
        } elseif ($role === TaskAssignment::ROLE_CONTROLLER) {
            $task->update(['controller_id' => $userId]);
        }

        $this->invalidateTaskCache($task->tenant_id);

        $this->logAction(
            'task_assigned',
            'TaskAssignment',
            $assignment->id,
            [
                'task_id' => $taskId,
                'user_id' => $userId,
                'role' => $role,
            ],
            $correlationId
        );

        return $assignment;
    }

    /**
     * Acknowledge task assignment
     */
    public function acknowledgeAssignment(int $assignmentId, string $correlationId = ''): TaskAssignment
    {
        $assignment = TaskAssignment::findOrFail($assignmentId);
        $assignment->acknowledge();

        $this->logAction(
            'task_assignment_acknowledged',
            'TaskAssignment',
            $assignment->id,
            [
                'task_id' => $assignment->task_id,
                'user_id' => $assignment->user_id,
            ],
            $correlationId
        );

        return $assignment->fresh();
    }

    private function invalidateTaskCache(int $tenantId): void
    {
        $this->cache->tags(["tasks:{$tenantId}"])->flush();
    }
}
