<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Jobs;

use App\Domains\Supermarket\Services\InventoryReservationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * ReleaseExpiredReservationsJob - освобождение истекших резервов.
 *
 * Запускается по расписанию (каждые 5 минут).
 * Освобождает резервы, которые истекли и не привязаны к заказам.
 */
final readonly class ReleaseExpiredReservationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        $this->onQueue('supermarket-low');
    }

    public function handle(
        InventoryReservationService $inventoryReservationService,
        LoggerInterface $logger,
    ): void {
        try {
            $logger->info('Releasing expired inventory reservations');

            $releasedCount = $inventoryReservationService->releaseExpiredReservations();

            $logger->info('Expired inventory reservations released', [
                'released_count' => $releasedCount,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Failed to release expired inventory reservations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
