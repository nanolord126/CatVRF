<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\ConsumableDeducted;
use App\Services\InventoryManagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;
use App\Services\FraudControlService;

/**
 * UpdateConsumableInventory
 *
 * Списывает каждый расходник из склада через InventoryManagementService.
 */
final class UpdateConsumableInventory implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private InventoryManagementService $inventory,
        private LoggerInterface            $auditLogger,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    public function handle(ConsumableDeducted $event): void
    {
        if (empty($event->consumables)) {
            return;
        }

        $this->db->transaction(function () use ($event): void {
            foreach ($event->consumables as $consumable) {
                $this->inventory->deductStock(
                    (int) $consumable['id'],
                    (int) $consumable['quantity'],
                    'appointment_completed',
                    'appointment',
                    $event->appointmentId,
                );
            }

            $this->auditLogger->info('Consumable inventory updated via event.', [
                'appointment_id'    => $event->appointmentId,
                'consumables_count' => count($event->consumables),
                'correlation_id'    => $event->correlationId,
            ]);
        });
    }

    public function failed(ConsumableDeducted $event, \Throwable $exception): void
    {
        $this->auditLogger->error('UpdateConsumableInventory listener failed.', [
            'appointment_id' => $event->appointmentId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
