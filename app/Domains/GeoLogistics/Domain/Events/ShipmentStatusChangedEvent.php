<?php

declare(strict_types=1);

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
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\GeoLogistics\Domain\Events
 */
final class ShipmentStatusChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $shipmentId,
        public readonly ShipmentStatus $oldStatus,
        public readonly ShipmentStatus $newStatus,
        public readonly string $correlationId) {}
}
