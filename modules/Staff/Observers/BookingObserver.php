<?php declare(strict_types=1);

namespace Modules\Staff\Observers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingObserver extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function updated(Booking $booking): void
        {
            // Check if status changed to 'completed' or 'checked_out'
            // According to migration status defaults to 'pending'
            // Let's assume 'checked_out' means housekeeping is needed.
            if ($booking->isDirty('status') && $booking->status === 'checked_out') {
                $room = $booking->room;
                
                StaffTask::create([
                    'title' => "Clean Room #{$room->number}",
                    'description' => "Housekeeping needed after checkout of booking #{$booking->id}",
                    'status' => 'TODO',
                    'priority' => 'high',
                    'taskable_id' => $room->id,
                    'taskable_type' => get_class($room),
                    'correlation_id' => (string) Str::uuid(),
                ]);
                
                // Also mark room as requiring housekeeping if the field exists
                $room->update(['requires_housekeeping' => true]);
            }
        }
}
