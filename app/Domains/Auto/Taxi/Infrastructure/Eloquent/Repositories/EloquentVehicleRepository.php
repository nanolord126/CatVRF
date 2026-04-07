<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories;

use App\Domains\Auto\Taxi\Domain\Entities\Vehicle as VehicleEntity;
use App\Domains\Auto\Taxi\Domain\Enums\VehicleClassEnum;
use App\Domains\Auto\Taxi\Domain\Repository\VehicleRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\VehicleId;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Vehicle as VehicleModel;

/**
 * Class EloquentVehicleRepository
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
final class EloquentVehicleRepository implements VehicleRepositoryInterface
{
    /**
     * Handle findById operation.
     *
     * @throws \DomainException
     */
    public function findById(VehicleId $id): ?VehicleEntity
    {
        $model = VehicleModel::find($id->toString());
        return $model ? $this->toEntity($model) : null;
    }

    public function save(VehicleEntity $vehicle): void
    {
        $vehicleData = $vehicle->toArray();
        VehicleModel::updateOrCreate(
            ['id' => $vehicle->getId()->toString()],
            $vehicleData
        );
    }

    private function toEntity(VehicleModel $model): VehicleEntity
    {
        return new VehicleEntity(
            id: new VehicleId($model->id),
            brand: $model->brand,
            model: $model->model,
            licensePlate: $model->license_plate,
            class: VehicleClassEnum::from($model->class),
            isInUse: $model->is_in_use,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );
    }
}
