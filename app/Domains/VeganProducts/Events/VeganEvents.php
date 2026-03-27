<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Events;

use App\Domains\VeganProducts\Models\VeganProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * VeganProductCreatedEvent - Layer 7/9: Event system.
 * Dispatched when a new plant-based product is registered in the system.
 * Requirement: SerializesModels, correlation_id in constructor.
 */
class VeganProductCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly VeganProduct $product,
        public readonly int $userId,
        public readonly string $correlationId,
        public readonly array $meta = [],
    ) {}
}

/**
 * VeganStockAlertEvent - Triggered when stock falls below threshold.
 */
class VeganStockAlertEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly VeganProduct $product,
        public readonly int $currentStock,
        public readonly string $correlationId,
    ) {}
}

/**
 * VeganSubscriptionRenewedEvent - Triggered after a subscription box is processed.
 */
class VeganSubscriptionRenewedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $boxId,
        public readonly string $correlationId,
    ) {}
}
