<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Events;

use App\Domains\Electronics\Models\ElectronicsProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ElectronicsProductCreated - Event triggered when a new gadget enters the system.
 * Layer: Events & Listeners (7/9)
 * Requirement: Dispatchable, correlation_id in constructor, audit log.
 */
final class ElectronicsProductCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly ElectronicsProduct $product,
        public readonly string $correlationId,
    ) {
        Log::channel('audit')->info('LAYER-7: ElectronicsProductCreated EVENT', [
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
        public readonly string $correlationId,
    ) {
        Log::channel('audit')->info('LAYER-7: ElectronicsOrderProcessed EVENT', [
            'order_id' => $orderId,
            'product_id' => $productId,
            'qty' => $quantity,
            'correlation_id' => $correlationId,
        ]);
    }
}
