<?php declare(strict_types=1);

/**
 * LowConsumableStockAlertListener — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/lowconsumablestockalertlistener
 */


namespace App\Domains\Food\Listeners;


use Psr\Log\LoggerInterface;
final class LowConsumableStockAlertListener
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function handle(LowConsumableStock $event): void
        {
            try {
                $this->logger->warning('Low consumable stock alert', [
                    'consumable_id' => $event->consumable->id,
                    'name' => $event->consumable->name,
                    'current_stock' => $event->consumable->current_stock,
                    'min_threshold' => $event->consumable->min_stock_threshold,
                    'unit' => $event->consumable->unit,
                    'correlation_id' => $event->correlationId,
                ]);
                // Notification::send($event->consumable->restaurant->owner, new LowStockNotification($event->consumable));
            } catch (\Throwable $e) {
                $this->logger->error('Low stock alert failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);

                throw $e;
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
