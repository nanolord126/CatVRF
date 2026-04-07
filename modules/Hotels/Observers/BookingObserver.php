<?php declare(strict_types=1);

namespace Modules\Hotels\Observers;

use Modules\Hotels\Models\Booking;
use Modules\Hotels\Models\Room;
use Modules\Staff\Models\StaffTask;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class BookingObserver
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Автоматическое создание задачи на уборку при выезде.
     */
    public function updated(Booking $booking): void
    {
        if ($booking->isDirty('status') && $booking->status === 'checked_out') {
            $correlationId = $booking->correlation_id ?? (string) Str::uuid();

            StaffTask::create([
                'title' => "Deep Cleaning: Room {$booking->room->room_number}",
                'description' => "Automated task generated on checkout of booking #{$booking->id}.",
                'priority' => 'high',
                'status' => 'not-started',
                'due_date' => now()->addHours(2),
                'assigned_to_role' => 'HOUSEKEEPER',
                'taskable_id' => $booking->room_id,
                'taskable_type' => Room::class,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('AUTO_TASK_GENERATION', [
                'user_id' => 0,
                'resource_id' => $booking->id,
                'resource_type' => Booking::class,
                'description' => "System generated housekeeping task for Room ID {$booking->room_id} after checkout.",
                'correlation_id' => $correlationId,
            ]);
        }
    }
}

