<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Repositories;

use Modules\Taxi\Domain\Entities\Ride;
use Modules\Taxi\Domain\ValueObjects\RideId;

interface RideRepositoryInterface
{
    public function save(Ride $ride): Ride;

    public function findById(RideId $id): ?Ride;

    public function findByUser(int $userId): array;

    public function findByDriver(int $driverId): array;

    public function findByStatus(string $status): array;

    public function delete(RideId $id): void;
}
