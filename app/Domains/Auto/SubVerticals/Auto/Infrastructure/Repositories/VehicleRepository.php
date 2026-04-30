<?php

declare(strict_types=1);

namespace Modules\Auto\Infrastructure\Repositories;

use Modules\Auto\Domain\Entities\Vehicle;
use Modules\Auto\Domain\Repositories\VehicleRepositoryInterface;
use Modules\Auto\Domain\ValueObjects\VehicleId;
use Modules\Auto\Domain\ValueObjects\LicensePlate;
use Modules\Auto\Models\Vehicle as VehicleModel;

final class VehicleRepository implements VehicleRepositoryInterface
{
    public function save(Vehicle $vehicle): Vehicle
    {
        $model = $vehicle->id
            ? VehicleModel::find($vehicle->id->value)
            : new VehicleModel();

        $model->tenant_id = $vehicle->tenantId;
        $model->type = $vehicle->type->value;
        $model->license_plate = $vehicle->licensePlate->value;
        $model->make = $vehicle->make;
        $model->model = $vehicle->model;
        $model->year = $vehicle->year;
        $model->color = $vehicle->color;
        $model->mileage = $vehicle->mileage;
        $model->metadata = $vehicle->metadata;
        $model->created_at = $vehicle->createdAt;
        $model->updated_at = $vehicle->updatedAt ?? now();

        $model->save();

        return $vehicle->withId(new VehicleId($model->id));
    }

    public function findById(VehicleId $id): ?Vehicle
    {
        $model = VehicleModel::find($id->value);

        if (!$model) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByLicensePlate(LicensePlate $licensePlate): ?Vehicle
    {
        $model = VehicleModel::where('license_plate', $licensePlate->value)->first();

        if (!$model) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByTenant(int $tenantId): array
    {
        $models = VehicleModel::where('tenant_id', $tenantId)->get();

        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function delete(VehicleId $id): void
    {
        VehicleModel::destroy($id->value);
    }

    private function modelToEntity(VehicleModel $model): Vehicle
    {
        return new Vehicle(
            id: new VehicleId($model->id),
            tenantId: $model->tenant_id,
            type: new \Modules\Auto\Domain\ValueObjects\VehicleType($model->type),
            licensePlate: new LicensePlate($model->license_plate),
            make: $model->make,
            model: $model->model,
            year: $model->year,
            color: $model->color,
            mileage: $model->mileage,
            metadata: $model->metadata ?? [],
            createdAt: \Carbon\CarbonImmutable::parse($model->created_at),
            updatedAt: $model->updated_at ? \Carbon\CarbonImmutable::parse($model->updated_at) : null,
        );
    }
}
