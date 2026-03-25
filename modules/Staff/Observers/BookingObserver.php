declare(strict_types=1);

<?php

namespace Modules\Staff\Observers;

use Modules\Hotels\Models\Booking;
use Modules\Staff\Models\StaffTask;
use Illuminate\Support\Str;

/**
 * BookingObserver
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BookingObserver
{
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
