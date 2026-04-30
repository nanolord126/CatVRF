<?php

declare(strict_types=1);

namespace Modules\Taxi\Infrastructure\Repositories;

use Modules\Taxi\Domain\Entities\Ride;
use Modules\Taxi\Domain\Repositories\RideRepositoryInterface;
use Modules\Taxi\Domain\ValueObjects\RideId;

final class RideRepository implements RideRepositoryInterface
{
    public function save(Ride $ride): Ride
    {
        // Placeholder implementation
        return $ride;
    }

    public function findById(RideId $id): ?Ride
    {
        // Placeholder implementation
        return null;
    }

    public function findByUser(int $userId): array
    {
        // Placeholder implementation
        return [];
    }

    public function findByDriver(int $driverId): array
    {
        // Placeholder implementation
        return [];
    }

    public function findByStatus(string $status): array
    {
        // Placeholder implementation
        return [];
    }

    public function delete(RideId $id): void
    {
        // Placeholder implementation
    }
}
