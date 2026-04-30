<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Repositories;

use Modules\Auto\Domain\Entities\VehicleMaintenance;
use Modules\Auto\Domain\ValueObjects\VehicleId;

interface VehicleMaintenanceRepositoryInterface
{
    public function save(VehicleMaintenance $maintenance): VehicleMaintenance;

    public function findById(int $id): ?VehicleMaintenance;

    public function findByVehicle(VehicleId $vehicleId): array;

    public function findOverdue(): array;

    public function findScheduled(int $vehicleId): array;

    public function delete(int $id): void;
}
