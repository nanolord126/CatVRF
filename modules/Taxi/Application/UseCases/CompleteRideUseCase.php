<?php

declare(strict_types=1);

namespace Modules\Taxi\Application\UseCases;

use Modules\Taxi\Domain\Entities\Ride;
use Modules\Taxi\Domain\Events\RideCompleted;
use Modules\Taxi\Domain\Repositories\RideRepositoryInterface;
use Modules\Taxi\Domain\ValueObjects\RideId;
use Illuminate\Support\Facades\Event as LaravelEvent;

final readonly class CompleteRideUseCase
{
    public function __construct(
        private RideRepositoryInterface $repository,
    ) {
    }

    public function execute(int $rideId, float $actualPrice): Ride
    {
        $ride = $this->repository->findById(new RideId($rideId));

        if (!$ride) {
            throw new \InvalidArgumentException('Ride not found');
        }

        if (!$ride->canBeCancelled()) {
            throw new \InvalidArgumentException('Ride cannot be completed in current status');
        }

        $completedRide = $ride->complete($actualPrice);
        $savedRide = $this->repository->save($completedRide);

        // Dispatch domain event
        LaravelEvent::dispatch(new RideCompleted(
            ride: $savedRide,
        ));

        return $savedRide;
    }
}
