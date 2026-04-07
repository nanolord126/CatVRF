<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Auto\Taxi\Domain\Repository;

use App\Domains\Auto\Taxi\Domain\Entities\Driver;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use Illuminate\Support\Collection;

interface DriverRepositoryInterface
{
    public function findById(DriverId $id): ?Driver;

    public function save(Driver $driver): void;

    /**
     * @return Collection<int, Driver>
     */
    public function findAvailableDrivers(): Collection;
}
