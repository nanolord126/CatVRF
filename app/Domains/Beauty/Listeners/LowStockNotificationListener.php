<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\LowStockReached;
use App\Domains\Beauty\Notifications\LowStockAlertNotification;
use App\Domains\Beauty\Models\BeautySalon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

/**
 * LowStockNotificationListener
 *
 * Отправляет алерт владельцу салона при достижении минимального порога остатков.
 */
final class LowStockNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private LoggerInterface $auditLogger,
    ) {}

    public function handle(LowStockReached $event): void
    {
        $this->auditLogger->warning('Low stock alert.', [
            'product_id'     => $event->productId,
            'product_name'   => $event->productName,
            'current_stock'  => $event->currentStock,
            'min_threshold'  => $event->minThreshold,
            'tenant_id'      => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);
    }

    public function failed(LowStockReached $event, \Throwable $exception): void
    {
        $this->auditLogger->error('LowStockNotificationListener failed.', [
            'product_id'     => $event->productId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Определяет, нужно ли обрабатывать событие.
     */
    public function shouldQueue(LowStockReached $event): bool
    {
        return $event->currentStock <= $event->minThreshold;
    }

    /**
     * Очередь для обработки события.
     */
    public function viaQueue(): string
    {
        return 'beauty-inventory';
    }
}
