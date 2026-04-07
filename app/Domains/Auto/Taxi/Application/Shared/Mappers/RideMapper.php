<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\Shared\Mappers;

use App\Domains\Auto\Taxi\Application\Shared\DTOs\RideDTO;
use App\Domains\Auto\Taxi\Domain\Entities\Ride;

/**
 * Class RideMapper
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Auto\Taxi\Application\Shared\Mappers
 */
final class RideMapper
{
    /**
     * Handle toDTO operation.
     *
     * @throws \DomainException
     */
    public function toDTO(Ride $ride, string $correlationId): RideDTO
    {
        $rideArray = $ride->toArray();

        return new RideDTO(
            id: $rideArray['id'],
            clientId: $rideArray['client_id'],
            driverId: $rideArray['driver_id'],
            status: $ride->getStatus(),
            pickupLocation: $rideArray['pickup_location'],
            dropoffLocation: $rideArray['dropoff_location'],
            price: $rideArray['price'],
            createdAt: $rideArray['created_at'],
            updatedAt: $rideArray['updated_at'],
            correlationId: $correlationId,
        );
    }
}
