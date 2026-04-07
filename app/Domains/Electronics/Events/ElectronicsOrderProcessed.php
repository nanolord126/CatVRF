<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Events;

use Dispatchable, InteractsWithSockets, SerializesModels;
use Dispatchable;
use Psr\Log\LoggerInterface;

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
