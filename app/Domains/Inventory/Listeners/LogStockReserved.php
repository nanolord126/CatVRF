<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Listeners;

use App\Domains\Inventory\Events\StockReserved;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Логирует и аудитит событие резервирования товара.
 */
final readonly class LogStockReserved
{
    public function __construct(
        private LoggerInterface $logger,
        private AuditService    $audit,
    ) {}

    public function handle(StockReserved $event): void
    {
        $this->logger->info('Stock reserved', [
            'product_id'     => $event->productId,
            'warehouse_id'   => $event->warehouseId,
            'quantity'        => $event->quantity,
            'reservation_id' => $event->reservationId,
            'tenant_id'      => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);

        $this->audit->record(
            action: 'stock_reserved',
            subjectType: 'inventory_item',
            subjectId: $event->productId,
            newValues: [
                'warehouse_id'   => $event->warehouseId,
                'quantity'        => $event->quantity,
                'reservation_id' => $event->reservationId,
            ],
            correlationId: $event->correlationId,
        );
    }
}
