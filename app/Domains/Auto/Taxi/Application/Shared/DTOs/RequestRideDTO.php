<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\Shared\DTOs;

use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;

/**
 * Class RequestRideDTO
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Auto\Taxi\Application\Shared\DTOs
 */
final readonly class RequestRideDTO
{
    public function __construct(
        public int $clientId,
        public Coordinate $pickupLocation,
        public Coordinate $dropoffLocation,
        public string $correlationId) {

    }

    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'],
            pickupLocation: new Coordinate(
                latitude: $data['pickup_latitude'],
                longitude: $data['pickup_longitude'],
            ),
            dropoffLocation: new Coordinate(
                latitude: $data['dropoff_latitude'],
                longitude: $data['dropoff_longitude'],
            ),
            correlationId: $data['correlation_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'pickup_location' => $this->pickupLocation->toArray(),
            'dropoff_location' => $this->dropoffLocation->toArray(),
            'correlation_id' => $this->correlationId,
        ];
    }
}
