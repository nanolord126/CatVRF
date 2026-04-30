<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Work Order Service
 *
 * Manages warehouse work orders:
 * - Create work orders
 * - Assign to workers
 * - Track progress
 * - Complete work orders
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryWorkOrderService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create work order
     *
     * @param  string  $workOrderType  Work order type
     * @param  string  $title  Title
     * @param  string  $description  Description
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $priority  Priority (1-10)
     * @param  string|null  $dueDate  Due date
     * @param  array<array<string, mixed>>  $tasks  Tasks
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Work order ID
     */
    public function createWorkOrder(
        string $workOrderType,
        string $title,
        string $description,
        int $warehouseId,
        int $priority,
        ?string $dueDate,
        array $tasks,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $workOrderType,
            $title,
            $description,
            $warehouseId,
            $priority,
            $dueDate,
            $tasks,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $workOrderId = $this->db->table('inventory_work_orders')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'work_order_number' => $this->generateWorkOrderNumber(),
                'work_order_type' => $workOrderType,
                'title' => $title,
                'description' => $description,
                'warehouse_id' => $warehouseId,
                'priority' => $priority,
                'due_date' => $dueDate ? \Carbon\Carbon::parse($dueDate) : null,
                'status' => 'pending',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            foreach ($tasks as $task) {
                $this->db->table('inventory_work_order_tasks')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'work_order_id' => $workOrderId,
                    'task_name' => $task['name'],
                    'task_description' => $task['description'] ?? null,
                    'task_type' => $task['type'] ?? 'general',
                    'estimated_minutes' => $task['estimated_minutes'] ?? 0,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logCreated(
                entityType: 'InventoryWorkOrder',
                entityId: $workOrderId,
                context: [
                    'correlation_id' => $correlationId,
                    'work_order_number' => $this->generateWorkOrderNumber(),
                    'work_order_type' => $workOrderType,
                    'task_count' => count($tasks),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $workOrderId;
        });
    }

    /**
     * Assign work order to worker
     *
     * @param  int  $workOrderId  Work order ID
     * @param  int  $workerId  Worker user ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function assignWorkOrder(int $workOrderId, int $workerId, int $userId, int $tenantId): bool
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $workOrderId,
            $workerId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $this->db->table('inventory_work_orders')
                ->where('id', $workOrderId)
                ->update([
                    'assigned_to' => $workerId,
                    'assigned_at' => now(),
                    'status' => 'assigned',
                ]);

            $this->logAction(
                action: 'work_order_assigned',
                entityType: 'InventoryWorkOrder',
                entityId: $workOrderId,
                context: [
                    'correlation_id' => $correlationId,
                    'worker_id' => $workerId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Start work order
     *
     * @param  int  $workOrderId  Work order ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function startWorkOrder(int $workOrderId, int $userId, int $tenantId): bool
    {
        $correlationId = Str::uuid()->toString();

        $this->db->table('inventory_work_orders')
            ->where('id', $workOrderId)
            ->where('status', 'assigned')
            ->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'started_by' => $userId,
            ]);

        $this->logAction(
            action: 'work_order_started',
            entityType: 'InventoryWorkOrder',
            entityId: $workOrderId,
            context: [
                'correlation_id' => $correlationId,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return true;
    }

    /**
     * Complete work order task
     *
     * @param  int  $taskId  Task ID
     * @param  string  $result  Result
     * @param  string|null  $notes  Notes
     * @param  int  $actualMinutes  Actual minutes spent
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function completeTask(
        int $taskId,
        string $result,
        ?string $notes,
        int $actualMinutes,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $taskId,
            $result,
            $notes,
            $actualMinutes,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $this->db->table('inventory_work_order_tasks')
                ->where('id', $taskId)
                ->update([
                    'status' => 'completed',
                    'result' => $result,
                    'notes' => $notes,
                    'actual_minutes' => $actualMinutes,
                    'completed_by' => $userId,
                    'completed_at' => now(),
                ]);

            $task = $this->db->table('inventory_work_order_tasks')
                ->where('id', $taskId)
                ->first();

            $this->checkWorkOrderCompletion($task->work_order_id, $userId, $tenantId);

            $this->logAction(
                action: 'work_order_task_completed',
                entityType: 'InventoryWorkOrderTask',
                entityId: $taskId,
                context: [
                    'correlation_id' => $correlationId,
                    'result' => $result,
                    'actual_minutes' => $actualMinutes,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Complete work order
     *
     * @param  int  $workOrderId  Work order ID
     * @param  string  $completionNotes  Completion notes
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function completeWorkOrder(
        int $workOrderId,
        string $completionNotes,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $workOrderId,
            $completionNotes,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $this->db->table('inventory_work_orders')
                ->where('id', $workOrderId)
                ->where('status', 'in_progress')
                ->update([
                    'status' => 'completed',
                    'completion_notes' => $completionNotes,
                    'completed_at' => now(),
                    'completed_by' => $userId,
                ]);

            $this->logAction(
                action: 'work_order_completed',
                entityType: 'InventoryWorkOrder',
                entityId: $workOrderId,
                context: [
                    'correlation_id' => $correlation_id,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Check if work order is complete
     *
     * @param  int  $workOrderId  Work order ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return void
     */
    private function checkWorkOrderCompletion(int $workOrderId, int $userId, int $tenantId): void
    {
        $tasks = $this->db->table('inventory_work_order_tasks')
            ->where('work_order_id', $workOrderId)
            ->get();

        $allCompleted = $tasks->every(fn ($t) => $t->status === 'completed');

        if ($allCompleted) {
            $this->completeWorkOrder($workOrderId, 'All tasks completed', $userId, $tenantId);
        }
    }

    /**
     * Get work order summary
     *
     * @param  int  $workOrderId  Work order ID
     * @return array Summary
     */
    public function getWorkOrderSummary(int $workOrderId): array
    {
        $workOrder = $this->db->table('inventory_work_orders')
            ->where('id', $workOrderId)
            ->first();

        if (! $workOrder) {
            throw new \RuntimeException("Work order {$workOrderId} not found");
        }

        $tasks = $this->db->table('inventory_work_order_tasks')
            ->where('work_order_id', $workOrderId)
            ->get();

        return [
            'work_order_id' => $workOrderId,
            'work_order_number' => $workOrder->work_order_number,
            'work_order_type' => $workOrder->work_order_type,
            'title' => $workOrder->title,
            'description' => $workOrder->description,
            'warehouse_id' => $workOrder->warehouse_id,
            'priority' => $workOrder->priority,
            'status' => $workOrder->status,
            'assigned_to' => $workOrder->assigned_to,
            'due_date' => $workOrder->due_date?->toIso8601String(),
            'created_at' => $workOrder->created_at->toIso8601String(),
            'started_at' => $workOrder->started_at?->toIso8601String(),
            'completed_at' => $workOrder->completed_at?->toIso8601String(),
            'tasks' => $tasks->map(fn ($t) => [
                'id' => $t->id,
                'task_name' => $t->task_name,
                'task_description' => $t->task_description,
                'status' => $t->status,
                'estimated_minutes' => $t->estimated_minutes,
                'actual_minutes' => $t->actual_minutes,
                'completed_at' => $t->completed_at?->toIso8601String(),
            ])->toArray(),
            'summary' => [
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $tasks->where('status', 'completed')->count(),
                'pending_tasks' => $tasks->where('status', 'pending')->count(),
                'total_estimated_minutes' => $tasks->sum('estimated_minutes'),
                'total_actual_minutes' => $tasks->sum('actual_minutes'),
                'completion_percentage' => $tasks->count() > 0
                    ? round(($tasks->where('status', 'completed')->count() / $tasks->count()) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Generate work order number
     *
     * @return string Work order number
     */
    private function generateWorkOrderNumber(): string
    {
        $prefix = 'WO';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('inventory_work_orders')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
