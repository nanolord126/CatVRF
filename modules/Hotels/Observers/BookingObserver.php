<?php declare(strict_types=1);

namespace Modules\Hotels\Observers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingObserver extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Автоматическое создание задачи на уборку при выезде. 
         */
        public function updated(Booking $booking)
        {
            // Проверяем, изменился ли статус на 'checked_out'
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
    
                // Логируем автоматизацию для аудита
                // Используем модель AuditLog (создана ранее)
                \App\Models\AuditLog::create([
                    'user_id' => 0, // 0 for System Action
                    'action' => 'AUTO_TASK_GENERATION',
                    'resource_id' => $booking->id,
                    'resource_type' => Booking::class,
                    'description' => "System generated housekeeping task for Room ID {$booking->room_id} after checkout.",
                    'correlation_id' => $correlationId,
                ]);
            }
        }
}
