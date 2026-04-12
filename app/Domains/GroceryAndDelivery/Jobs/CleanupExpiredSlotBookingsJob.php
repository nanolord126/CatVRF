<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Jobs;


use App\Domains\GroceryAndDelivery\Models\SlotBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Throwable;

/**
 * Очищает просроченные бронирования слотов доставки.
 *
 * Поток:
 * 1. Находит все неподтверждённые бронирования старше 20 минут.
 * 2. Удаляет их в рамках app(\Illuminate\Database\DatabaseManager::class)->transaction().
 * 3. Логирует количество удалённых записей с correlation_id.
 *
 * Запускается каждую минуту через scheduler.
 */
final class CleanupExpiredSlotBookingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $correlationId,
    ) {
        $this->onQueue('grocery-cleanup');
    }

    public function handle(): void
    {
        try {
            app(\Illuminate\Database\DatabaseManager::class)->transaction(function (): void {
                $expiredBookings = SlotBooking::where('is_confirmed', false)
                    ->where('booked_at', '<', now()->subMinutes(20))
                    ->get();

                $count = $expiredBookings->count();

                foreach ($expiredBookings as $booking) {
                    $booking->delete();
                }

                app(\Psr\Log\LoggerInterface::class)->channel('audit')->info('Expired slot bookings cleaned up', [
                    'count' => $count,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            app(\Psr\Log\LoggerInterface::class)->channel('audit')->error('CleanupExpiredSlotBookingsJob failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
        }
    }
}
