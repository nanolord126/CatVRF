<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Listeners;

use App\Domains\Inventory\Events\InventoryCheckCreated;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Логирует создание инвентаризации.
 */
final readonly class LogInventoryCheckCreated
{
    public function __construct(
        private LoggerInterface $logger,
        private AuditService    $audit,
    ) {}

    public function handle(InventoryCheckCreated $event): void
    {
        $this->logger->info('Inventory check created', [
            'inventory_check_id' => $event->inventoryCheckId,
            'warehouse_id'       => $event->warehouseId,
            'tenant_id'          => $event->tenantId,
            'correlation_id'     => $event->correlationId,
        ]);

        $this->audit->record(
            action: 'inventory_check_created',
            subjectType: 'inventory_check',
            subjectId: $event->inventoryCheckId,
            newValues: [
                'warehouse_id' => $event->warehouseId,
            ],
            correlationId: $event->correlationId,
        );
    }
}
