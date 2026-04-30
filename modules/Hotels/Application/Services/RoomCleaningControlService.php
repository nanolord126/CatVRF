<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Hotels\Infrastructure\Models\HousekeepingTaskModel;
use Modules\Hotels\Infrastructure\Models\RoomModel;
use Modules\Hotels\Domain\Entities\HousekeepingTask;

final readonly class RoomCleaningControlService
{
    use WithAuditLogging;

    private const CACHE_TTL = 1800;

    public function __construct(
        private AuditService $auditService
    ) {}

    public function assignCleanerToTask(
        int $taskId,
        int $cleanerId,
        ?string $userId = null,
        ?string $tenantId = null
    ): HousekeepingTask {
        $task = HousekeepingTaskModel::findOrFail($taskId);

        if ($task->status !== 'pending') {
            throw new \RuntimeException('Cannot assign cleaner to non-pending task');
        }

        $task->update([
            'assigned_to' => $cleanerId,
        ]);

        $this->auditService->logAction(
            action: 'housekeeping.cleaner_assigned',
            entityType: 'HousekeepingTask',
            entityId: $taskId,
            userId: $userId,
            tenantId: $tenantId ?? $task->tenant_id,
            context: [
                'cleaner_id' => $cleanerId,
                'room_id' => $task->room_id,
                'task_type' => $task->task_type,
            ]
        );

        $this->invalidateTaskCache($taskId);

        return $this->mapToEntity($task->fresh());
    }

    public function autoAssignCleaners(
        int $venueId,
        CarbonImmutable $date,
        ?string $userId = null,
        ?string $tenantId = null
    ): array {
        $pendingTasks = HousekeepingTaskModel::where('venue_id', $venueId)
            ->where('status', 'pending')
            ->whereDate('scheduled_for', $date)
            ->whereNull('assigned_to')
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_for')
            ->get();

        $availableCleaners = $this->getAvailableCleaners($venueId, $date);

        $assignments = [];
        $cleanerIndex = 0;

        foreach ($pendingTasks as $task) {
            if ($availableCleaners->isEmpty()) {
                break;
            }

            $cleaner = $availableCleaners[$cleanerIndex];
            
            try {
                $this->assignCleanerToTask($task->id, $cleaner->id, $userId, $tenantId);
                
                $assignments[] = [
                    'task_id' => $task->id,
                    'cleaner_id' => $cleaner->id,
                    'room_id' => $task->room_id,
                    'scheduled_for' => $task->scheduled_for,
                ];

                $cleanerIndex = ($cleanerIndex + 1) % $availableCleaners->count();
            } catch (\Exception $e) {
                Log::error('Failed to auto-assign cleaner', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->auditService->logAction(
            action: 'housekeeping.auto_assigned',
            entityType: 'Venue',
            entityId: $venueId,
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'date' => $date->format('Y-m-d'),
                'assigned_count' => count($assignments),
            ]
        );

        return $assignments;
    }

    public function getCleaningStatusBoard(
        int $venueId,
        CarbonImmutable $date
    ): array {
        $cacheKey = "cleaning_board_{$venueId}_{$date->format('Y-m-d')}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($venueId, $date) {
            $tasks = HousekeepingTaskModel::where('venue_id', $venueId)
                ->whereDate('scheduled_for', $date)
                ->with(['room', 'assignedToUser'])
                ->orderBy('scheduled_for')
                ->get();

            $rooms = RoomModel::where('venue_id', $venueId)
                ->orderBy('room_number')
                ->get();

            $board = [
                'date' => $date->format('Y-m-d'),
                'summary' => $this->calculateSummary($tasks),
                'rooms' => [],
                'tasks_by_status' => [
                    'pending' => [],
                    'in_progress' => [],
                    'completed' => [],
                    'overdue' => [],
                ],
                'cleaners_workload' => $this->calculateCleanersWorkload($tasks),
            ];

            foreach ($rooms as $room) {
                $roomTasks = $tasks->where('room_id', $room->id);
                $latestTask = $roomTasks->sortByDesc('scheduled_for')->first();

                $board['rooms'][] = [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'clean_status' => $room->clean_status,
                    'last_cleaned_at' => $room->last_cleaned_at?->format('Y-m-d H:i:s'),
                    'current_task' => $latestTask ? $this->mapToEntity($latestTask)->toArray() : null,
                    'task_count' => $roomTasks->count(),
                ];
            }

            foreach ($tasks as $task) {
                $entity = $this->mapToEntity($task);
                
                if ($entity->isOverdue()) {
                    $board['tasks_by_status']['overdue'][] = $entity->toArray();
                } else {
                    $board['tasks_by_status'][$task->status][] = $entity->toArray();
                }
            }

            return $board;
        });
    }

    public function getCleaningAnalytics(
        int $venueId,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate
    ): array {
        $tasks = HousekeepingTaskModel::where('venue_id', $venueId)
            ->whereBetween('scheduled_for', [$fromDate, $toDate])
            ->get();

        return [
            'period' => [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d'),
            ],
            'total_tasks' => $tasks->count(),
            'by_status' => [
                'pending' => $tasks->where('status', 'pending')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
            ],
            'by_type' => [
                'clean' => $tasks->where('task_type', 'clean')->count(),
                'deep_clean' => $tasks->where('task_type', 'deep_clean')->count(),
                'turn_down' => $tasks->where('task_type', 'turn_down')->count(),
                'inspection' => $tasks->where('task_type', 'inspection')->count(),
            ],
            'performance' => [
                'average_duration' => $tasks->whereNotNull('actual_minutes')->avg('actual_minutes'),
                'on_time_completion_rate' => $this->calculateOnTimeRate($tasks),
                'overdue_count' => $tasks->filter(fn($t) => $t->status === 'pending' && $t->scheduled_for->isPast())->count(),
            ],
            'by_priority' => [
                'urgent' => $tasks->where('priority', 'urgent')->count(),
                'high' => $tasks->where('priority', 'high')->count(),
                'normal' => $tasks->where('priority', 'normal')->count(),
                'low' => $tasks->where('priority', 'low')->count(),
            ],
        ];
    }

    public function createCleaningSchedule(
        int $venueId,
        CarbonImmutable $date,
        array $roomIds,
        string $taskType = 'clean',
        ?string $userId = null,
        ?string $tenantId = null
    ): array {
        $createdTasks = [];

        foreach ($roomIds as $roomId) {
            try {
                $room = RoomModel::findOrFail($roomId);
                
                $taskModel = HousekeepingTaskModel::create([
                    'tenant_id' => $room->tenant_id,
                    'venue_id' => $venueId,
                    'room_id' => $roomId,
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'task_type' => $taskType,
                    'priority' => 'normal',
                    'status' => 'pending',
                    'scheduled_for' => $date,
                    'estimated_minutes' => 30,
                    'checklist' => $this->getDefaultChecklist($taskType),
                ]);

                $createdTasks[] = $this->mapToEntity($taskModel);

                $this->auditService->logCreated(
                    entityType: 'HousekeepingTask',
                    entityId: $taskModel->id,
                    userId: $userId,
                    tenantId: $tenantId ?? $room->tenant_id,
                    context: [
                        'room_id' => $roomId,
                        'venue_id' => $venueId,
                        'scheduled_for' => $date->format('Y-m-d H:i:s'),
                        'task_type' => $taskType,
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Failed to create cleaning task', [
                    'room_id' => $roomId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $createdTasks;
    }

    public function updateTaskPriority(
        int $taskId,
        string $newPriority,
        ?string $userId = null,
        ?string $tenantId = null
    ): HousekeepingTask {
        $task = HousekeepingTaskModel::findOrFail($taskId);

        $oldPriority = $task->priority;
        $task->update(['priority' => $newPriority]);

        $this->auditService->logAction(
            action: 'housekeeping.priority_updated',
            entityType: 'HousekeepingTask',
            entityId: $taskId,
            userId: $userId,
            tenantId: $tenantId ?? $task->tenant_id,
            context: [
                'old_priority' => $oldPriority,
                'new_priority' => $newPriority,
            ]
        );

        $this->invalidateTaskCache($taskId);

        return $this->mapToEntity($task->fresh());
    }

    public function getCleanerWorkload(
        int $cleanerId,
        CarbonImmutable $date
    ): array {
        $tasks = HousekeepingTaskModel::where('assigned_to', $cleanerId)
            ->whereDate('scheduled_for', $date)
            ->get();

        return [
            'cleaner_id' => $cleanerId,
            'date' => $date->format('Y-m-d'),
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'in_progress_tasks' => $tasks->where('status', 'in_progress')->count(),
            'pending_tasks' => $tasks->where('status', 'pending')->count(),
            'total_estimated_minutes' => $tasks->sum('estimated_minutes'),
            'total_actual_minutes' => $tasks->whereNotNull('actual_minutes')->sum('actual_minutes'),
            'tasks' => $tasks->map(fn($t) => $this->mapToEntity($t)->toArray())->toArray(),
        ];
    }

    private function getAvailableCleaners(int $venueId, CarbonImmutable $date)
    {
        $existingAssignments = HousekeepingTaskModel::where('venue_id', $venueId)
            ->whereDate('scheduled_for', $date)
            ->where('status', '!=', 'completed')
            ->pluck('assigned_to')
            ->unique()
            ->toArray();

        return \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hotel_cleaner'))
            ->where('is_active', true)
            ->whereNotIn('id', $existingAssignments)
            ->get();
    }

    private function calculateSummary($tasks): array
    {
        return [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'overdue' => $tasks->filter(fn($t) => $t->status === 'pending' && $t->scheduled_for->isPast())->count(),
        ];
    }

    private function calculateCleanersWorkload($tasks): array
    {
        $workload = [];

        foreach ($tasks->whereNotNull('assigned_to')->groupBy('assigned_to') as $cleanerId => $cleanerTasks) {
            $workload[] = [
                'cleaner_id' => $cleanerId,
                'task_count' => $cleanerTasks->count(),
                'estimated_minutes' => $cleanerTasks->sum('estimated_minutes'),
            ];
        }

        return $workload;
    }

    private function calculateOnTimeRate($tasks): float
    {
        $completed = $tasks->where('status', 'completed');
        
        if ($completed->isEmpty()) {
            return 0.0;
        }

        $onTime = $completed->filter(fn($t) => 
            $t->completed_at && $t->completed_at->lte($t->scheduled_for)
        )->count();

        return ($onTime / $completed->count()) * 100;
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

    private function invalidateTaskCache(int $taskId): void
    {
        $task = HousekeepingTaskModel::find($taskId);
        
        if (!$task) {
            return;
        }

        $date = $task->scheduled_for ? CarbonImmutable::parse($task->scheduled_for) : now();
        $cacheKey = "cleaning_board_{$task->venue_id}_{$date->format('Y-m-d')}";
        
        Cache::forget($cacheKey);
    }
}
