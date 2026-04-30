<?php

declare(strict_types=1);

namespace Modules\Auto\Application\UseCases;

use Modules\Auto\Application\DTOs\VehicleDto;
use Modules\Auto\Domain\Entities\Vehicle;
use Modules\Auto\Domain\Events\VehicleCreated;
use Modules\Auto\Domain\Exceptions\InvalidVehicleDataException;
use Modules\Auto\Domain\Repositories\VehicleRepositoryInterface;
use Modules\Auto\Domain\ValueObjects\LicensePlate;
use Modules\Auto\Domain\ValueObjects\VehicleType;
use Modules\Auto\Domain\ValueObjects\VehicleId;
use Illuminate\Support\Facades\Event as LaravelEvent;

final readonly class CreateVehicleUseCase
{
    public function __construct(
        private VehicleRepositoryInterface $repository,
    ) {
    }

    public function execute(VehicleDto $dto): Vehicle
    {
        $this->validatePayload($dto);

        $vehicle = Vehicle::create(
            tenantId: $dto->tenantId,
            type: new VehicleType($dto->type),
            licensePlate: new LicensePlate($dto->licensePlate),
            make: $dto->make,
            model: $dto->model,
            year: $dto->year,
            color: $dto->color,
            mileage: $dto->mileage,
            metadata: $dto->metadata,
        );

        $savedVehicle = $this->repository->save($vehicle);

        // Dispatch domain event
        LaravelEvent::dispatch(new VehicleCreated(
            vehicle: $savedVehicle,
        ));

        return $savedVehicle;
    }

    private function validatePayload(VehicleDto $dto): void
    {
        if (empty($dto->make)) {
            throw new InvalidVehicleDataException('Vehicle make is required');
        }

        if (empty($dto->model)) {
            throw new InvalidVehicleDataException('Vehicle model is required');
        }

        if ($dto->year < 1900 || $dto->year > (int) date('Y') + 1) {
            throw new InvalidVehicleDataException('Invalid vehicle year');
        }

        if ($dto->mileage !== null && $dto->mileage < 0) {
            throw new InvalidVehicleDataException('Mileage cannot be negative');
        }
    }
}
