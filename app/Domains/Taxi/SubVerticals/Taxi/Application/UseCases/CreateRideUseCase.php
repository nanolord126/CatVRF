<?php

declare(strict_types=1);

namespace Modules\Taxi\Application\UseCases;

use Modules\Taxi\Application\DTOs\RideDto;
use Modules\Taxi\Domain\Entities\Ride;
use Modules\Taxi\Domain\Events\RideRequested;
use Modules\Taxi\Domain\Exceptions\InvalidRideDataException;
use Modules\Taxi\Domain\Repositories\RideRepositoryInterface;
use Modules\Taxi\Domain\ValueObjects\Location;
use Modules\Taxi\Domain\ValueObjects\RideId;
use Modules\Taxi\Domain\ValueObjects\RideStatus;
use Illuminate\Support\Facades\Event as LaravelEvent;

final readonly class CreateRideUseCase
{
    public function __construct(
        private RideRepositoryInterface $repository,
    ) {
    }

    public function execute(RideDto $dto): Ride
    {
        $this->validatePayload($dto);

        $ride = Ride::create(
            tenantId: $dto->tenantId,
            userId: $dto->userId,
            driverId: $dto->driverId,
            pickupLocation: new Location($dto->pickupLatitude, $dto->pickupLongitude, $dto->pickupAddress),
            dropoffLocation: new Location($dto->dropoffLatitude, $dto->dropoffLongitude, $dto->dropoffAddress),
            estimatedPrice: $dto->estimatedPrice,
            metadata: $dto->metadata,
        );

        $savedRide = $this->repository->save($ride);

        // Dispatch domain event
        LaravelEvent::dispatch(new RideRequested(
            ride: $savedRide,
        ));

        return $savedRide;
    }

    private function validatePayload(RideDto $dto): void
    {
        if ($dto->userId <= 0) {
            throw new InvalidRideDataException('User ID must be positive');
        }

        if ($dto->driverId <= 0) {
            throw new InvalidRideDataException('Driver ID must be positive');
        }

        if ($dto->estimatedPrice <= 0) {
            throw new InvalidRideDataException('Estimated price must be positive');
        }

        if ($dto->pickupLatitude < -90 || $dto->pickupLatitude > 90) {
            throw new InvalidRideDataException('Invalid pickup latitude');
        }

        if ($dto->pickupLongitude < -180 || $dto->pickupLongitude > 180) {
            throw new InvalidRideDataException('Invalid pickup longitude');
        }

        if ($dto->dropoffLatitude < -90 || $dto->dropoffLatitude > 90) {
            throw new InvalidRideDataException('Invalid dropoff latitude');
        }

        if ($dto->dropoffLongitude < -180 || $dto->dropoffLongitude > 180) {
            throw new InvalidRideDataException('Invalid dropoff longitude');
        }
    }
}
