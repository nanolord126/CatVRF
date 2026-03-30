<?php declare(strict_types=1);

namespace App\Domains\Hotels\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LogBookingCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(BookingCreated $event): void
        {
            Log::channel('audit')->info('Hotel Booking Created Audit', [
                'booking_uuid' => $event->booking->uuid,
                'correlation_id' => $event->correlationId,
                'tenant_id' => $event->booking->tenant_id,
            ]);
        }
}
