<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Listeners;

use App\Domains\Inventory\Events\StockUpdated;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Логирует любое изменение остатков.
 */
final readonly class LogStockUpdated
{
    public function __construct(
        private LoggerInterface $logger,
        private AuditService    $audit,
    ) {}

    public function handle(StockUpdated $event): void
    {
        $this->logger->info('Stock updated', [
            'product_id'     => $event->productId,
            'warehouse_id'   => $event->warehouseId,
            'quantity'        => $event->newQuantity,
            'reserved'       => $event->newReserved,
            'available'      => $event->available,
            'tenant_id'      => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);

        $this->audit->record(
            action: 'stock_updated',
            subjectType: 'inventory_item',
            subjectId: $event->productId,
            newValues: [
                'warehouse_id' => $event->warehouseId,
                'quantity'      => $event->newQuantity,
                'reserved'     => $event->newReserved,
                'available'    => $event->available,
            ],
            correlationId: $event->correlationId,
        );
    }
}
