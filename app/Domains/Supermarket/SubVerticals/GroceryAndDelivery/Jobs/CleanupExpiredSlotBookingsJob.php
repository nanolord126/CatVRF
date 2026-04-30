<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Jobs;

use LoggerInterface;

use Carbon\CarbonImmutable;

use App\Domains\GroceryAndDelivery\Models\SlotBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Очищает просроченные бронирования слотов доставки.
 *
 * Поток:
 * 1. Находит все неподтверждённые бронирования старше 20 минут.
 * 2. Удаляет их в рамках $this->databaseManager /* TODO: inject via DI */->transaction().
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

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly DatabaseManager $databaseManager,
        public readonly string $correlationId,) {
        $this->onQueue('grocery-cleanup');
    }

    public function tags(): array
    {
        return ['groceryanddelivery', 'job'];
    }

    public function handle(DatabaseManager $db, LoggerInterface $logger): void
    {
        try {
            $db->transaction(function () use ($logger): void {
                $expiredBookings = SlotBooking::where('is_confirmed', false)
                    ->where('booked_at', '<', \Carbon\CarbonImmutable::now()->subMinutes(20))
                    ->get();

                $count = $expiredBookings->count();

                foreach ($expiredBookings as $booking) {
                    $booking->delete();
                }

                $logger->channel('audit')->$this->logger->info('Expired slot bookings cleaned up', [
                    'count' => $count,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            $logger->channel('audit')->error('CleanupExpiredSlotBookingsJob failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */  // failed() no method injection->error('groceryanddelivery job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
