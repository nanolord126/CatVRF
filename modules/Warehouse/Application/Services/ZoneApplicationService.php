<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\Repositories\ZoneRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Enums\ZoneTypeEnum;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Psr\Log\LoggerInterface;
use App\Services\Security\AuditService;
use Illuminate\Support\Str;

/**
 * Zone Application Service - Production Layer
 *
 * Orchestrates warehouse zone operations (physical zones within warehouse)
 * Separate from Inventory (stock counting process)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ZoneApplicationService
{
    public function __construct(
        private readonly ZoneRepositoryInterface $zoneRepository,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    /**
     * Create a new zone in warehouse
     */
    public function createZone(
        string $warehouseId,
        string $name,
        ZoneTypeEnum $type,
        int $capacity,
        int $tenantId,
        ?string $branchId = null,
        ?int $userId = null
    ): WarehouseZone {
        $warehouse = $this->warehouseRepository->findById(WarehouseId::fromString($warehouseId));

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        $zone = WarehouseZone::create(
            warehouseId: $warehouse->getId(),
            name: $name,
            type: $type,
            capacity: $capacity,
            branchId: $branchId
        );

        $correlationId = Str::uuid()->toString();

        $this->zoneRepository->save($zone);

        $this->auditService->logEvent(
            eventType: 'warehouse_zone_created',
            context: [
                'entity_type' => 'warehouse_zone',
                'entity_id' => $zone->getId()->toString(),
                'warehouse_id' => $warehouseId,
                'name' => $zone->getName(),
                'type' => $zone->getType()->value,
                'capacity' => $zone->getCapacity(),
                'branch_id' => $branchId,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse zone created', [
            'zone_id' => $zone->getId()->toString(),
            'warehouse_id' => $warehouseId,
            'name' => $zone->getName(),
            'type' => $zone->getType()->value,
            'capacity' => $zone->getCapacity(),
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $zone;
    }

    /**
     * Update zone stock level
     */
    public function updateZoneStock(
        string $zoneId,
        int $quantity,
        int $tenantId,
        ?int $userId = null
    ): WarehouseZone {
        $id = ZoneId::fromString($zoneId);
        $zone = $this->zoneRepository->findById($id);

        if (!$zone) {
            throw new \InvalidArgumentException('Zone not found');
        }

        $updatedZone = $zone->updateStock($quantity);
        $correlationId = Str::uuid()->toString();

        $this->zoneRepository->save($updatedZone);

        $this->auditService->logEvent(
            eventType: 'warehouse_zone_stock_updated',
            context: [
                'entity_type' => 'warehouse_zone',
                'entity_id' => $zoneId,
                'warehouse_id' => $updatedZone->getWarehouseId()->toString(),
                'quantity_change' => $quantity,
                'new_stock' => $updatedZone->getCurrentStock(),
                'utilization' => $updatedZone->getUtilizationPercentage(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Zone stock updated', [
            'zone_id' => $zoneId,
            'quantity_change' => $quantity,
            'new_stock' => $updatedZone->getCurrentStock(),
            'correlation_id' => $correlationId,
        ]);

        return $updatedZone;
    }

    /**
     * Get zone by ID
     */
    public function getZone(string $zoneId): ?WarehouseZone
    {
        $id = ZoneId::fromString($zoneId);
        return $this->zoneRepository->findById($id);
    }

    /**
     * Get zones by warehouse
     */
    public function getZonesByWarehouse(string $warehouseId): array
    {
        $id = WarehouseId::fromString($warehouseId);
        return $this->zoneRepository->findByWarehouseId($id);
    }

    /**
     * Get active zones by warehouse
     */
    public function getActiveZonesByWarehouse(string $warehouseId): array
    {
        $id = WarehouseId::fromString($warehouseId);
        return $this->zoneRepository->findActiveByWarehouseId($id);
    }

    /**
     * Delete zone
     */
    public function deleteZone(
        string $zoneId,
        int $tenantId,
        ?int $userId = null
    ): void {
        $id = ZoneId::fromString($zoneId);
        $zone = $this->zoneRepository->findById($id);

        if (!$zone) {
            throw new \InvalidArgumentException('Zone not found');
        }

        $correlationId = Str::uuid()->toString();

        $this->zoneRepository->delete($id);

        $this->auditService->logEvent(
            eventType: 'warehouse_zone_deleted',
            context: [
                'entity_type' => 'warehouse_zone',
                'entity_id' => $zoneId,
                'warehouse_id' => $zone->getWarehouseId()->toString(),
                'name' => $zone->getName(),
                'type' => $zone->getType()->value,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Zone deleted', [
            'zone_id' => $zoneId,
            'name' => $zone->getName(),
            'correlation_id' => $correlationId,
        ]);
    }
}
