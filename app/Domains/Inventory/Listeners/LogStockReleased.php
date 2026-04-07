<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Listeners;

use App\Domains\Inventory\Events\StockReleased;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Логирует и аудитит событие снятия резерва.
 */
final readonly class LogStockReleased
{
    public function __construct(
        private LoggerInterface $logger,
        private AuditService    $audit,
    ) {}

    public function handle(StockReleased $event): void
    {
        $this->logger->info('Stock released', [
            'product_id'     => $event->productId,
            'warehouse_id'   => $event->warehouseId,
            'quantity'        => $event->quantity,
            'tenant_id'      => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);

        $this->audit->record(
            action: 'stock_released',
            subjectType: 'inventory_item',
            subjectId: $event->productId,
            newValues: [
                'warehouse_id' => $event->warehouseId,
                'quantity'      => $event->quantity,
            ],
            correlationId: $event->correlationId,
        );
    }
}
