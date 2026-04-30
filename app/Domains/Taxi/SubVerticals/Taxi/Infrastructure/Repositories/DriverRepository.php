<?php

declare(strict_types=1);

namespace Modules\Taxi\Infrastructure\Repositories;

use Modules\Taxi\Domain\Entities\Driver;
use Modules\Taxi\Domain\Repositories\DriverRepositoryInterface;

final class DriverRepository implements DriverRepositoryInterface
{
    public function save(Driver $driver): Driver
    {
        // Placeholder implementation
        return $driver;
    }

    public function findById(int $id): ?Driver
    {
        // Placeholder implementation
        return null;
    }

    public function findByUser(int $userId): ?Driver
    {
        // Placeholder implementation
        return null;
    }

    public function findAvailableDrivers(): array
    {
        // Placeholder implementation
        return [];
    }

    public function findByStatus(string $status): array
    {
        // Placeholder implementation
        return [];
    }

    public function delete(int $id): void
    {
        // Placeholder implementation
    }
}
