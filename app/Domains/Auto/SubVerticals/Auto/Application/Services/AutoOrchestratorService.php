<?php

declare(strict_types=1);

namespace Modules\Auto\Application\Services;

use Modules\Auto\Application\DTOs\VehicleDto;
use Modules\Auto\Application\DTOs\VehicleMaintenanceDto;
use Modules\Auto\Application\UseCases\CreateVehicleUseCase;
use Modules\Auto\Application\UseCases\ScheduleMaintenanceUseCase;
use Modules\Auto\Domain\Entities\Vehicle;
use Modules\Auto\Domain\Entities\VehicleMaintenance;
use Modules\Auto\Domain\Repositories\VehicleRepositoryInterface;
use Modules\Auto\Domain\Repositories\VehicleMaintenanceRepositoryInterface;
use Modules\Auto\Domain\ValueObjects\VehicleId;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class AutoOrchestratorService
{
    use WithAuditLogging;

    public function __construct(
        private CreateVehicleUseCase $createVehicleUseCase,
        private ScheduleMaintenanceUseCase $scheduleMaintenanceUseCase,
        private VehicleRepositoryInterface $vehicleRepository,
        private VehicleMaintenanceRepositoryInterface $maintenanceRepository,
        private readonly AuditService $auditService,
    ) {
    }

    public function createVehicle(VehicleDto $dto): Vehicle
    {
        $vehicle = $this->createVehicleUseCase->execute($dto);
        
        $this->logCreated(
            entityType: 'Vehicle',
            entityId: $vehicle->id?->value ?? null,
            context: [
                'make' => $dto->make ?? null,
                'model' => $dto->model ?? null,
                'vin' => $dto->vin ?? null,
            ],
            userId: null,
            tenantId: $dto->tenantId ?? null
        );
        
        return $vehicle;
    }

    public function scheduleMaintenance(VehicleMaintenanceDto $dto): VehicleMaintenance
    {
        $maintenance = $this->scheduleMaintenanceUseCase->execute($dto);
        
        $this->logCreated(
            entityType: 'VehicleMaintenance',
            entityId: $maintenance->id ?? null,
            context: [
                'vehicle_id' => $dto->vehicleId ?? null,
                'scheduled_date' => $dto->scheduledDate ?? null,
                'type' => $dto->type ?? null,
            ],
            userId: null,
            tenantId: $dto->tenantId ?? null
        );
        
        return $maintenance;
    }

    public function getVehicle(int $vehicleId): ?Vehicle
    {
        return $this->vehicleRepository->findById(new VehicleId($vehicleId));
    }

    public function getVehicleMaintenance(int $vehicleId): array
    {
        return $this->maintenanceRepository->findByVehicle(new VehicleId($vehicleId));
    }

    public function getOverdueMaintenance(): array
    {
        return $this->maintenanceRepository->findOverdue();
    }

    public function createVehicleWithInitialMaintenance(VehicleDto $vehicleDto, VehicleMaintenanceDto $maintenanceDto): array
    {
        $vehicle = $this->createVehicle($vehicleDto);

        $maintenanceDto->vehicleId = $vehicle->id?->value ?? 0;
        $maintenance = $this->scheduleMaintenance($maintenanceDto);

        return [
            'vehicle' => $vehicle,
            'maintenance' => $maintenance,
        ];
    }
}
