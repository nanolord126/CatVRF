<?php

declare(strict_types=1);
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class ShipmentStatusChangedEvent
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class ShipmentStatusChangedEvent
{
    public function __construct(
        public readonly int $shipmentId,
        public readonly ShipmentStatus $oldStatus,
        public readonly ShipmentStatus $newStatus,
        public readonly string $correlationId
    ) {}
}
