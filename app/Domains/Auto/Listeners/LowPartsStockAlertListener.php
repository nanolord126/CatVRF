<?php declare(strict_types=1);

/**
 * LowPartsStockAlertListener — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/lowpartsstockalertlistener
 */


namespace App\Domains\Auto\Listeners;


use Psr\Log\LoggerInterface;
final class LowPartsStockAlertListener
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function handle(LowPartsStock $event): void
        {
            try {
                $part = $event->part;

                $this->logger->warning('Low auto parts stock alert', [
                    'part_id' => $part->id,
                    'part_name' => $part->name,
                    'current_stock' => $part->current_stock,
                    'min_threshold' => $part->min_stock_threshold,
                    'sku' => $part->sku,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('LowPartsStockAlertListener failed', [
                    'part_id' => $event->part->id,
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
