<?php declare(strict_types=1);

/**
 * ElectronicsProductCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/electronicsproductcreated
 */


namespace App\Domains\Electronics\Events;


use Psr\Log\LoggerInterface;
final class ElectronicsProductCreated
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        /**
         * Create a new event instance.
         */
        public function __construct(
            public readonly ElectronicsProduct $product,
            public readonly string $correlationId, public readonly LoggerInterface $logger) {
            $this->logger->info('LAYER-7: ElectronicsProductCreated EVENT', [
                'sku' => $product->sku,
                'name' => $product->name,
                'correlation_id' => $correlationId,
            ]);
        }
    }

    /**
     * ElectronicsOrderProcessed - Event triggered after a successful gadget sale and stock lock.
     */
    final class ElectronicsOrderProcessed
    {
        use Dispatchable;

        public function __construct(
            public readonly int $orderId,
            public readonly int $productId,
            public readonly int $quantity,
            public readonly string $correlationId) {
            $this->logger->info('LAYER-7: ElectronicsOrderProcessed EVENT', [
                'order_id' => $orderId,
                'product_id' => $productId,
                'qty' => $quantity,
                'correlation_id' => $correlationId,
            ]);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
