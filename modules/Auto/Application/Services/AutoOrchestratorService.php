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
use App\Traits\WithAnalyticsTracking;
use App\Services\Security\AuditService;
use Modules\Analytics\Services\BehavioralTracker;
use Modules\Analytics\Services\RFMService;
use Modules\Analytics\Application\Services\VerticalAnalyticsIntegrationService;
use Modules\Analytics\Application\Services\AnalyticsOrchestratorService;
use App\Services\Fraud\FraudControlService;
use Illuminate\Database\DatabaseManager;

final readonly class AutoOrchestratorService
{
    use WithAuditLogging;
    use WithAnalyticsTracking;

    public function __construct(
        private CreateVehicleUseCase $createVehicleUseCase,
        private ScheduleMaintenanceUseCase $scheduleMaintenanceUseCase,
        private readonly AuditService $auditService,
        private readonly FraudControlService $fraudControl,
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly VehicleMaintenanceRepositoryInterface $maintenanceRepository,
        private readonly DatabaseManager $db,
    ) {
    }

    public function createVehicle(VehicleDto $dto): Vehicle
    {
        // FRAUD CHECK - мандаторно первым действием (Canon 1)
        $fraudResult = $this->fraudControl->checkRequest([
            'operation_type' => 'create_vehicle',
            'vertical' => 'auto',
            'user_id' => $dto->userId ?? null,
            'tenant_id' => $dto->tenantId ?? null,
            'amount' => 0,
            'ip_address' => request()->ip(),
            'action' => 'create_vehicle',
        ]);

        if ($fraudResult['should_block']) {
            $this->logAction(
                action: 'blocked_by_fraud',
                entityType: 'Vehicle',
                context: [
                    'fraud_score' => $fraudResult['fraud_score'],
                    'indicators' => $fraudResult['indicators'],
                    'vin' => $dto->vin ?? null,
                ],
                userId: $dto->userId ?? null,
                tenantId: $dto->tenantId ?? null
            );
            throw new \RuntimeException('Vehicle creation blocked by fraud detection');
        }

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
        // FRAUD CHECK - мандаторно первым действием (Canon 1)
        $fraudResult = $this->fraudControl->checkRequest([
            'operation_type' => 'schedule_maintenance',
            'vertical' => 'auto',
            'user_id' => $dto->userId ?? null,
            'tenant_id' => $dto->tenantId ?? null,
            'amount' => $dto->estimatedCost ?? 0,
            'action' => 'schedule_maintenance',
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logAction(
                action: 'blocked_by_fraud',
                entityType: 'VehicleMaintenance',
                context: [
                    'fraud_score' => $fraudResult['fraud_score'],
                    'indicators' => $fraudResult['indicators'],
                    'vehicle_id' => $dto->vehicleId ?? null,
                ],
                userId: $dto->userId ?? null,
                tenantId: $dto->tenantId ?? null
            );
            throw new \RuntimeException('Maintenance scheduling blocked by fraud detection');
        }

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
        return $this->db->transaction(function () use ($vehicleDto, $maintenanceDto) {
            $vehicle = $this->createVehicle($vehicleDto);

            // Create new DTO with vehicleId instead of modifying readonly property
            $updatedMaintenanceDto = new VehicleMaintenanceDto(
                id: null,
                vehicleId: $vehicle->id?->value ?? 0,
                maintenanceType: $maintenanceDto->maintenanceType,
                description: $maintenanceDto->description,
                cost: $maintenanceDto->cost,
                scheduledDate: $maintenanceDto->scheduledDate,
                completedAt: $maintenanceDto->completedAt,
                status: $maintenanceDto->status,
                metadata: $maintenanceDto->metadata,
            );
            $maintenance = $this->scheduleMaintenance($updatedMaintenanceDto);

            return [
                'vehicle' => $vehicle,
                'maintenance' => $maintenance,
            ];
        });
    }
}
