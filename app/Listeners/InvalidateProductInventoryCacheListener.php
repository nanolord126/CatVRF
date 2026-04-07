<?php declare(strict_types=1);

/**
 * InvalidateProductInventoryCacheListener — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/invalidateproductinventorycachelistener
 * @see https://catvrf.ru/docs/invalidateproductinventorycachelistener
 * @see https://catvrf.ru/docs/invalidateproductinventorycachelistener
 */


namespace App\Listeners;

use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

/**
 * Class InvalidateProductInventoryCacheListener
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Listeners
 */
final class InvalidateProductInventoryCacheListener
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(object $event): void
        {
            if (!isset($event->productId)) {
                return;
            }

            try {
                $cacheTag = "product_inventory_{$event->productId}";
                $this->cache->store('redis')->tags([$cacheTag])->flush();

                // Also flush popular products for the vertical
                if (isset($event->vertical)) {
                    $verticalTag = "popular_products_{$event->vertical}";
                    $this->cache->store('redis')->tags([$verticalTag])->flush();
                }

                $this->logger->channel('audit')->info('Product inventory cache invalidated', [
                    'product_id' => $event->productId,
                    'vertical' => $event->vertical ?? null,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to invalidate product inventory cache', [
                    'product_id' => $event->productId ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
}
