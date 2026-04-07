<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories;

use App\Domains\Auto\Taxi\Domain\Entities\Driver as DriverEntity;
use App\Domains\Auto\Taxi\Domain\Repository\DriverRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\VehicleId;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Driver as DriverModel;
use Illuminate\Support\Collection;

/**
 * Class EloquentDriverRepository
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
 * @package App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories
 */
final class EloquentDriverRepository implements DriverRepositoryInterface
{
    public function findById(DriverId $id): ?DriverEntity
    {
        $model = DriverModel::find($id->toString());
        return $model ? $this->toEntity($model) : null;
    }

    public function save(DriverEntity $driver): void
    {
        $driverData = $driver->toArray();
        DriverModel::updateOrCreate(
            ['id' => $driver->getId()->toString()],
            $driverData
        );
    }

    public function findAvailableDrivers(): Collection
    {
        return DriverModel::where('is_available', true)
            ->get()
            ->map(fn (DriverModel $model) => $this->toEntity($model));
    }

    private function toEntity(DriverModel $model): DriverEntity
    {
        return new DriverEntity(
            id: new DriverId($model->id),
            name: $model->name,
            licenseNumber: $model->license_number,
            isAvailable: $model->is_available,
            vehicleId: $model->vehicle_id ? new VehicleId($model->vehicle_id) : null,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );
    }
}
