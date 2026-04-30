<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Repositories;

use Modules\Taxi\Domain\Entities\Driver;

interface DriverRepositoryInterface
{
    public function save(Driver $driver): Driver;

    public function findById(int $id): ?Driver;

    public function findByUser(int $userId): ?Driver;

    public function findAvailableDrivers(): array;

    public function findByStatus(string $status): array;

    public function delete(int $id): void;
}
