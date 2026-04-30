<?php

declare(strict_types=1);

namespace Modules\Auto\Application\UseCases;

use Modules\Auto\Application\DTOs\VehicleMaintenanceDto;
use Modules\Auto\Domain\Entities\VehicleMaintenance;
use Modules\Auto\Domain\Events\VehicleMaintenanceScheduled;
use Modules\Auto\Domain\Repositories\VehicleMaintenanceRepositoryInterface;
use Modules\Auto\Domain\ValueObjects\VehicleId;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event as LaravelEvent;

final readonly class ScheduleMaintenanceUseCase
{
    public function __construct(
        private VehicleMaintenanceRepositoryInterface $repository,
    ) {
    }

    public function execute(VehicleMaintenanceDto $dto): VehicleMaintenance
    {
        $this->validatePayload($dto);

        $maintenance = VehicleMaintenance::schedule(
            vehicleId: new VehicleId($dto->vehicleId),
            maintenanceType: $dto->maintenanceType,
            description: $dto->description,
            cost: $dto->cost,
            scheduledDate: CarbonImmutable::parse($dto->scheduledDate),
            metadata: $dto->metadata,
        );

        $savedMaintenance = $this->repository->save($maintenance);

        // Dispatch domain event
        LaravelEvent::dispatch(new VehicleMaintenanceScheduled(
            maintenance: $savedMaintenance,
        ));

        return $savedMaintenance;
    }

    private function validatePayload(VehicleMaintenanceDto $dto): void
    {
        if ($dto->vehicleId <= 0) {
            throw new \InvalidArgumentException('Vehicle ID must be positive');
        }

        if (empty($dto->maintenanceType)) {
            throw new \InvalidArgumentException('Maintenance type is required');
        }

        if ($dto->cost < 0) {
            throw new \InvalidArgumentException('Cost cannot be negative');
        }
    }
}
