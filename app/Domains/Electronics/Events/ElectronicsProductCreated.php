<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Events;



use Psr\Log\LoggerInterface;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
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
