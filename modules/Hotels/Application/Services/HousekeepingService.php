<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Hotels\Domain\Entities\HousekeepingTask;
use Modules\Hotels\Infrastructure\Models\HousekeepingTaskModel;
use Modules\Hotels\Infrastructure\Models\RoomModel;

final class HousekeepingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function scheduleCheckoutCleaning(
        int $venueId,
        int $roomId,
        CarbonImmutable $scheduledFor,
        ?int $bookingItemId = null,
        string $priority = 'normal',
    ): HousekeepingTask {
        $task = HousekeepingTask::create(
            tenantId: tenant()->id,
            venueId: $venueId,
            roomId: $roomId,
            taskType: 'clean',
            priority: $priority,
            scheduledFor: $scheduledFor,
            estimatedMinutes: 30,
            bookingItemId: $bookingItemId,
            checklist: $this->getDefaultChecklist('clean'),
        );

        $taskModel = HousekeepingTaskModel::create([
            'tenant_id' => $task->tenantId,
            'venue_id' => $task->venueId,
            'room_id' => $task->roomId,
            'uuid' => $task->uuid,
            'task_type' => $task->taskType,
            'priority' => $task->priority,
            'status' => $task->status,
            'scheduled_for' => $task->scheduledFor,
            'estimated_minutes' => $task->estimatedMinutes,
            'booking_item_id' => $task->bookingItemId,
            'checklist' => $task->checklist,
        ]);

        $this->logCreated('HousekeepingTask', $taskModel->id, null, $task->tenantId, [
            'room_id' => $roomId,
            'venue_id' => $venueId,
            'scheduled_for' => $scheduledFor,
            'task_type' => 'clean',
            'priority' => $priority,
        ]);

        return new HousekeepingTask(
            ...$task->toArray(),
            id: $taskModel->id,
        );
    }

    public function scheduleCleaning(int $roomId, CarbonImmutable $scheduledFor): HousekeepingTaskModel
    {
        $room = RoomModel::findOrFail($roomId);

        $taskModel = HousekeepingTaskModel::create([
            'tenant_id' => $room->tenant_id,
            'venue_id' => $room->venue_id,
            'room_id' => $roomId,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'task_type' => 'checkout_clean',
            'priority' => 'normal',
            'status' => 'pending',
            'scheduled_for' => $scheduledFor,
            'estimated_minutes' => 30,
            'checklist' => $this->getDefaultChecklist('clean'),
        ]);

        $this->logCreated('HousekeepingTask', $taskModel->id, null, $room->tenant_id, [
            'room_id' => $roomId,
            'scheduled_for' => $scheduledFor,
            'task_type' => 'checkout_clean',
        ]);

        return $taskModel;
    }

    public function createTask(
        int $venueId,
        int $roomId,
        string $taskType = 'clean',
        string $priority = 'normal',
        ?CarbonImmutable $scheduledFor = null,
        int $estimatedMinutes = 30,
        ?int $assignedTo = null,
        ?int $bookingItemId = null,
    ): HousekeepingTask {
        $task = HousekeepingTask::create(
            tenantId: tenant()->id,
            venueId: $venueId,
            roomId: $roomId,
            taskType: $taskType,
            priority: $priority,
            scheduledFor: $scheduledFor,
            estimatedMinutes: $estimatedMinutes,
            assignedTo: $assignedTo,
            bookingItemId: $bookingItemId,
            checklist: $this->getDefaultChecklist($taskType),
        );

        $taskModel = HousekeepingTaskModel::create([
            'tenant_id' => $task->tenantId,
            'venue_id' => $task->venueId,
            'room_id' => $task->roomId,
            'uuid' => $task->uuid,
            'task_type' => $task->taskType,
            'priority' => $task->priority,
            'status' => $task->status,
            'scheduled_for' => $task->scheduledFor,
            'estimated_minutes' => $task->estimatedMinutes,
            'assigned_to' => $task->assignedTo,
            'booking_item_id' => $task->bookingItemId,
            'checklist' => $task->checklist,
        ]);

        return new HousekeepingTask(
            ...$task->toArray(),
            id: $taskModel->id,
        );
    }

    public function startTask(int $taskId, int $assignedTo): HousekeepingTask
    {
        return DB::transaction(function () use ($taskId, $assignedTo) {
            $taskModel = HousekeepingTaskModel::findOrFail($taskId);

            if ($taskModel->status !== 'pending') {
                throw new \RuntimeException('Task is not in pending status');
            }

            $taskModel->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'assigned_to' => $assignedTo,
            ]);

            // Mark room as being cleaned
            RoomModel::where('id', $taskModel->room_id)->update(['clean_status' => 'inspected']);

            $this->logAction('housekeeping_task_started', 'HousekeepingTask', $taskId, [
                'room_id' => $taskModel->room_id,
                'assigned_to' => $assignedTo,
            ], $assignedTo, $taskModel->tenant_id);

            return $taskModel;
        });
    }

    public function completeTask(
        int $taskId,
        int $completedBy,
        ?array $completedChecklist = null,
        ?array $notes = null,
        ?array $photosAfter = null,
    ): HousekeepingTask {
        return DB::transaction(function () use (
            $taskId,
            $completedBy,
            $completedChecklist,
            $notes,
            $photosAfter,
        ) {
            $taskModel = HousekeepingTaskModel::findOrFail($taskId);

            if ($taskModel->status !== 'in_progress') {
                throw new \RuntimeException('Task is not in progress');
            }

            $actualMinutes = $taskModel->started_at 
                ? now()->diffInMinutes($taskModel->started_at) 
                : null;

            $taskModel->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => $completedBy,
                'actual_minutes' => $actualMinutes,
                'checklist' => $completedChecklist ?? $taskModel->checklist,
                'notes' => $notes,
                'photos_after' => $photosAfter,
            ]);

            // Mark room as clean
            RoomModel::where('id', $taskModel->room_id)->update([
                'clean_status' => 'clean',
                'last_cleaned_at' => now(),
                'last_cleaned_by' => $completedBy,
            ]);

            $this->logAction('housekeeping_task_completed', 'HousekeepingTask', $taskId, [
                'room_id' => $taskModel->room_id,
                'completed_by' => $completedBy,
                'actual_minutes' => $actualMinutes,
            ], $completedBy, $taskModel->tenant_id);

            return $this->mapToEntity($taskModel);
        });
    }

    public function getTasksForVenue(
        int $venueId,
        ?string $status = null,
        ?CarbonImmutable $date = null,
    ): array {
        $query = HousekeepingTaskModel::where('venue_id', $venueId)
            ->with(['room', 'assignedToUser'])
            ->orderBy('scheduled_for');

        if ($status) {
            $query->where('status', $status);
        }

        if ($date) {
            $query->whereDate('scheduled_for', $date);
        }

        return $query->get()->map(fn ($model) => $this->mapToEntity($model))->toArray();
    }

    public function getTasksForRoom(int $roomId, ?string $status = null): array
    {
        $query = HousekeepingTaskModel::where('room_id', $roomId)
            ->with(['room', 'assignedToUser'])
            ->orderBy('scheduled_for', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->map(fn ($model) => $this->mapToEntity($model))->toArray();
    }

    public function getOverdueTasks(int $venueId): array
    {
        return HousekeepingTaskModel::where('venue_id', $venueId)
            ->where('status', 'pending')
            ->where('scheduled_for', '<', now())
            ->with(['room', 'assignedToUser'])
            ->orderBy('scheduled_for')
            ->get()
            ->map(fn ($model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function getBoardData(int $venueId, CarbonImmutable $date): array
    {
        $tasks = $this->getTasksForVenue($venueId, null, $date);

        $grouped = [
            'pending' => [],
            'in_progress' => [],
            'completed' => [],
            'overdue' => [],
        ];

        foreach ($tasks as $task) {
            if ($task->isOverdue()) {
                $grouped['overdue'][] = $task;
            } else {
                $grouped[$task->status][] = $task;
            }
        }

        return $grouped;
    }

    private function getDefaultChecklist(string $taskType): array
    {
        return match ($taskType) {
            'clean' => [
                ['item' => 'Change bed linens', 'completed' => false],
                ['item' => 'Clean bathroom', 'completed' => false],
                ['item' => 'Vacuum floor', 'completed' => false],
                ['item' => 'Dust surfaces', 'completed' => false],
                ['item' => 'Restock amenities', 'completed' => false],
                ['item' => 'Empty trash', 'completed' => false],
            ],
            'deep_clean' => [
                ['item' => 'Deep clean bathroom', 'completed' => false],
                ['item' => 'Shampoo carpets', 'completed' => false],
                ['item' => 'Clean windows', 'completed' => false],
                ['item' => 'Clean under furniture', 'completed' => false],
                ['item' => 'Sanitize all surfaces', 'completed' => false],
            ],
            'turn_down' => [
                ['item' => 'Turn down bed', 'completed' => false],
                ['item' => 'Close curtains', 'completed' => false],
                ['item' => 'Place water bottles', 'completed' => false],
                ['item' => 'Remove used towels', 'completed' => false],
            ],
            'inspection' => [
                ['item' => 'Check room cleanliness', 'completed' => false],
                ['item' => 'Check amenities', 'completed' => false],
                ['item' => 'Check for damages', 'completed' => false],
                ['item' => 'Report issues', 'completed' => false],
            ],
            default => [],
        };
    }

    private function mapToEntity(HousekeepingTaskModel $model): HousekeepingTask
    {
        return new HousekeepingTask(
            id: $model->id,
            tenantId: $model->tenant_id,
            venueId: $model->venue_id,
            roomId: $model->room_id,
            uuid: $model->uuid,
            taskType: $model->task_type,
            priority: $model->priority,
            status: $model->status,
            scheduledFor: $model->scheduled_for ? CarbonImmutable::parse($model->scheduled_for) : null,
            startedAt: $model->started_at ? CarbonImmutable::parse($model->started_at) : null,
            completedAt: $model->completed_at ? CarbonImmutable::parse($model->completed_at) : null,
            assignedTo: $model->assigned_to,
            completedBy: $model->completed_by,
            checklist: $model->checklist,
            notes: $model->notes,
            photosBefore: $model->photos_before,
            photosAfter: $model->photos_after,
            estimatedMinutes: $model->estimated_minutes,
            actualMinutes: $model->actual_minutes,
            bookingItemId: $model->booking_item_id,
            createdAt: CarbonImmutable::parse($model->created_at),
            updatedAt: CarbonImmutable::parse($model->updated_at),
        );
    }
}
