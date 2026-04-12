<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Jobs;


use Carbon\Carbon;

use App\Domains\Inventory\Models\Reservation;
use App\Domains\Inventory\Services\InventoryService;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Очистка просроченных резервов (каждую минуту).
 *
 * B2C: TTL 20 минут.
 * B2B: TTL до 7 дней (задаётся при создании резерва).
 *
 * CANON: LoggerInterface и AuditService — ТОЛЬКО в handle(), не в конструкторе.
 */
final class ReservationCleanupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('inventory');
    }

    public function handle(InventoryService $inventoryService, LoggerInterface $logger, AuditService $audit): void
    {
        $correlationId = Str::uuid()->toString();

        $expired = Reservation::where('expires_at', '<', Carbon::now())->get();

        if ($expired->isEmpty()) {
            return;
        }

        $released = 0;

        foreach ($expired as $reservation) {
            try {
                $inventoryService->releaseReservation($reservation->id, $correlationId);
                $released++;
            } catch (\Throwable $e) {
                $logger->error('Failed to release expired reservation', [
                    'reservation_id' => $reservation->id,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        $logger->info('Reservation cleanup completed', [
            'expired_count'  => $expired->count(),
            'released_count' => $released,
            'correlation_id' => $correlationId,
        ]);

        $audit->record(
            action: 'reservation_cleanup',
            subjectType: 'reservation',
            subjectId: null,
            newValues: ['expired' => $expired->count(), 'released' => $released],
            correlationId: $correlationId,
        );
    }
}
