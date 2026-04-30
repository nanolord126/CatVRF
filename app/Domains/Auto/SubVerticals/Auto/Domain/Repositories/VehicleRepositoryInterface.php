<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Repositories;

use Modules\Auto\Domain\Entities\Vehicle;
use Modules\Auto\Domain\ValueObjects\VehicleId;
use Modules\Auto\Domain\ValueObjects\LicensePlate;

interface VehicleRepositoryInterface
{
    public function save(Vehicle $vehicle): Vehicle;

    public function findById(VehicleId $id): ?Vehicle;

    public function findByLicensePlate(LicensePlate $licensePlate): ?Vehicle;

    public function findByTenant(int $tenantId): array;

    public function delete(VehicleId $id): void;
}
