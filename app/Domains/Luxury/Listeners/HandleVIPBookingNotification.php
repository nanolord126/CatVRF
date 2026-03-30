<?php declare(strict_types=1);

namespace App\Domains\Luxury\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleVIPBookingNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Обработка уведомления
         */
        public function handle(VIPBookingCreated $event): void
        {
            try {
                // 1. Audit log в Listener
                Log::channel('audit')->info('VIP Booking Listener Triggered', [
                    'booking_uuid' => $event->booking->uuid,
                    'correlation_id' => $event->correlationId,
                ]);

                // 2. Имитация оправки уведомления консьержу
                // Notification::send($concierge, new ConciergeNewBookingNotification($event->booking));

            } catch (Throwable $e) {
                Log::channel('audit')->error('VIP Booking Notification Error', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
            }
        }
}
