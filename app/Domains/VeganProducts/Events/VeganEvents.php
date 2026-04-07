<?php declare(strict_types=1);

/**
 * VeganProductCreatedEvent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/veganproductcreatedevent
 */


namespace App\Domains\VeganProducts\Events;

final class VeganProductCreatedEvent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        /**
         * Create a new event instance.
         */
        public function __construct(
            private readonly VeganProduct $product,
            private readonly int $userId,
            private readonly string $correlationId,
            private array $meta = []) {}
    }

    /**
     * VeganStockAlertEvent - Triggered when stock falls below threshold.
     */
    final class VeganStockAlertEvent
    {
        use Dispatchable, SerializesModels;

        public function __construct(
            private readonly VeganProduct $product,
            private readonly int $currentStock,
            private readonly string $correlationId) {}
    }

    /**
     * VeganSubscriptionRenewedEvent - Triggered after a subscription box is processed.
     */
    final class VeganSubscriptionRenewedEvent
    {
        use Dispatchable, SerializesModels;

        public function __construct(
            private readonly int $subscriptionId,
            private readonly int $boxId,
            private readonly string $correlationId) {}
}
