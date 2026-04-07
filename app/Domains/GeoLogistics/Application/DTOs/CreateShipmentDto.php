<?php

declare(strict_types=1);

/**
 * Class CreateShipmentDto
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\GeoLogistics\Application\DTOs
 */
final readonly class CreateShipmentDto
{
    public function __construct(
        public string $tenantId,
        public int $deliveryOrderId,
        public float $pickupLat,
        public float $pickupLng,
        public float $dropoffLat,
        public float $dropoffLng,
        public string $correlationId) {}
}
