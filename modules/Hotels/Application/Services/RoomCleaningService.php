<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use Modules\Hotels\Domain\Entities\RoomCleaning;
use Modules\Hotels\Domain\Entities\Room;
use Illuminate\Support\Facades\DB;

final class RoomCleaningService
{
    public function __construct(
        private readonly string $correlationId,
    ) {}

    public function scheduleCleaning(int $roomId, string $cleaningType, \DateTime $scheduledAt, ?int $staffId = null): RoomCleaning
    {
        $room = Room::findOrFail($roomId);

        return DB::transaction(function () use ($room, $cleaningType, $scheduledAt, $staffId) {
            $cleaning = RoomCleaning::create([
                'tenant_id' => $room->tenant_id,
                'hotel_id' => $room->hotel_id,
                'room_id' => $room->id,
                'cleaning_type' => $cleaningType,
                'status' => 'pending',
                'scheduled_at' => $scheduledAt,
                'assigned_staff_id' => $staffId,
                'priority' => $this->calculatePriority($cleaningType),
                'correlation_id' => $this->correlationId,
            ]);

            // Create task for staff if assigned
            if ($staffId) {
                \Modules\CatCRM\Domain\Entities\Task::create([
                    'tenant_id' => $room->tenant_id,
                    'assigned_to_id' => $staffId,
                    'title' => "Уборка номера {$room->room_number}",
                    'type' => 'cleaning',
                    'priority' => $cleaning->priority,
                    'due_date' => $scheduledAt,
                    'entity_type' => 'room_cleaning',
                    'entity_id' => $cleaning->id,
                ]);
            }

            return $cleaning;
        });
    }

    public function assignStaff(int $cleaningId, int $staffId): RoomCleaning
    {
        $cleaning = RoomCleaning::findOrFail($cleaningId);
        
        $cleaning->update(['assigned_staff_id' => $staffId]);

        // Create or update task
        $task = \Modules\CatCRM\Domain\Entities\Task::where('entity_type', 'room_cleaning')
            ->where('entity_id', $cleaning->id)
            ->first();

        if ($task) {
            $task->update(['assigned_to_id' => $staffId]);
        } else {
            \Modules\CatCRM\Domain\Entities\Task::create([
                'tenant_id' => $cleaning->tenant_id,
                'assigned_to_id' => $staffId,
                'title' => "Уборка номера {$cleaning->room->room_number}",
                'type' => 'cleaning',
                'priority' => $cleaning->priority,
                'due_date' => $cleaning->scheduled_at,
                'entity_type' => 'room_cleaning',
                'entity_id' => $cleaning->id,
            ]);
        }

        return $cleaning->fresh();
    }

    public function autoAssignCleaners(int $hotelId, \DateTime $date): int
    {
        $pendingCleanings = RoomCleaning::where('hotel_id', $hotelId)
            ->where('status', 'pending')
            ->whereDate('scheduled_at', $date)
            ->whereNull('assigned_staff_id')
            ->get();

        $staff = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hotel_cleaner'))
            ->where('is_active', true)
            ->get();

        $assignedCount = 0;
        $staffIndex = 0;

        foreach ($pendingCleanings as $cleaning) {
            if ($staff->isEmpty()) break;

            $this->assignStaff($cleaning->id, $staff[$staffIndex]->id);
            $staffIndex = ($staffIndex + 1) % $staff->count();
            $assignedCount++;
        }

        return $assignedCount;
    }

    public function getCleaningStats(int $hotelId, \DateTime $date): array
    {
        $cleanings = RoomCleaning::where('hotel_id', $hotelId)
            ->whereDate('scheduled_at', $date)
            ->get();

        return [
            'total' => $cleanings->count(),
            'pending' => $cleanings->where('status', 'pending')->count(),
            'in_progress' => $cleanings->where('status', 'in_progress')->count(),
            'completed' => $cleanings->where('status', 'completed')->count(),
            'overdue' => $cleanings->filter(fn($c) => $c->status === 'pending' && $c->scheduled_at->isPast())->count(),
            'average_duration' => $cleanings->whereNotNull('duration_minutes')->avg('duration_minutes'),
            'average_quality_score' => $cleanings->whereNotNull('quality_score')->avg('quality_score'),
        ];
    }

    private function calculatePriority(string $cleaningType): int
    {
        return match ($cleaningType) {
            'checkout' => 5,
            'express' => 4,
            'standard' => 3,
            'deep' => 2,
            default => 3,
        };
    }
}
