<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Listeners;

use App\Domains\Inventory\Events\InventoryCheckUpdated;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Логирует обновление статуса инвентаризации.
 */
final readonly class LogInventoryCheckUpdated
{
    public function __construct(
        private LoggerInterface $logger,
        private AuditService    $audit,
    ) {}

    public function handle(InventoryCheckUpdated $event): void
    {
        $this->logger->info('Inventory check updated', [
            'inventory_check_id' => $event->inventoryCheckId,
            'old_status'         => $event->oldStatus,
            'new_status'         => $event->newStatus,
            'tenant_id'          => $event->tenantId,
            'correlation_id'     => $event->correlationId,
        ]);

        $this->audit->record(
            action: 'inventory_check_updated',
            subjectType: 'inventory_check',
            subjectId: $event->inventoryCheckId,
            oldValues: ['status' => $event->oldStatus],
            newValues: ['status' => $event->newStatus],
            correlationId: $event->correlationId,
        );
    }
}
