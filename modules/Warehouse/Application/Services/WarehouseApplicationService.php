<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Enums\WarehouseTypeEnum;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Events\WarehouseCreated;
use Psr\Log\LoggerInterface;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use App\Traits\WithAnalyticsTracking;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Warehouse Application Service - Production Layer
 *
 * Orchestrates warehouse operations (physical space management)
 * Separate from Inventory (stock counting process)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WarehouseApplicationService
{
    use WithAuditLogging;
    use WithAnalyticsTracking;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    /**
     * Create a new warehouse
     */
    public function createWarehouse(
        string $name,
        string $address,
        ?string $branchId,
        WarehouseTypeEnum $type,
        int $capacity,
        int $tenantId,
        ?int $userId = null
    ): Warehouse {
        $warehouse = Warehouse::create(
            name: $name,
            address: $address,
            branchId: $branchId,
            type: $type,
            capacity: $capacity
        );

        $correlationId = Str::uuid()->toString();

        $this->warehouseRepository->save($warehouse);

        $this->auditService->logEvent(
            eventType: 'warehouse_created',
            context: [
                'entity_type' => 'warehouse',
                'entity_id' => $warehouse->getId()->toString(),
                'name' => $warehouse->getName(),
                'type' => $warehouse->getType()->value,
                'capacity' => $warehouse->getCapacity(),
                'branch_id' => $branchId,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        Event::dispatch(new WarehouseCreated(
            warehouse: $warehouse,
            warehouseId: $warehouse->getId(),
            name: $warehouse->getName()
        ));

        $this->logger->info('Warehouse created', [
            'warehouse_id' => $warehouse->getId()->toString(),
            'name' => $warehouse->getName(),
            'type' => $warehouse->getType()->value,
            'capacity' => $warehouse->getCapacity(),
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $warehouse;
    }

    /**
     * Update warehouse stock level
     */
    public function updateStock(
        string $warehouseId,
        int $quantity,
        int $tenantId,
        ?int $userId = null
    ): Warehouse {
        $id = WarehouseId::fromString($warehouseId);
        $warehouse = $this->warehouseRepository->findById($id);

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        $updatedWarehouse = $warehouse->updateStock($quantity);
        $correlationId = Str::uuid()->toString();

        $this->warehouseRepository->save($updatedWarehouse);

        $this->auditService->logEvent(
            eventType: 'warehouse_stock_updated',
            context: [
                'entity_type' => 'warehouse',
                'entity_id' => $warehouseId,
                'quantity_change' => $quantity,
                'new_stock' => $updatedWarehouse->getCurrentStock(),
                'utilization' => $updatedWarehouse->getUtilizationPercentage(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse stock updated', [
            'warehouse_id' => $warehouseId,
            'quantity_change' => $quantity,
            'new_stock' => $updatedWarehouse->getCurrentStock(),
            'correlation_id' => $correlationId,
        ]);

        return $updatedWarehouse;
    }

    /**
     * Activate warehouse
     */
    public function activateWarehouse(
        string $warehouseId,
        int $tenantId,
        ?int $userId = null
    ): Warehouse {
        $id = WarehouseId::fromString($warehouseId);
        $warehouse = $this->warehouseRepository->findById($id);

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        $updatedWarehouse = $warehouse->activate();
        $correlationId = Str::uuid()->toString();

        $this->warehouseRepository->save($updatedWarehouse);

        $this->auditService->logEvent(
            eventType: 'warehouse_activated',
            context: [
                'entity_type' => 'warehouse',
                'entity_id' => $warehouseId,
                'name' => $warehouse->getName(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse activated', [
            'warehouse_id' => $warehouseId,
            'correlation_id' => $correlationId,
        ]);

        return $updatedWarehouse;
    }

    /**
     * Deactivate warehouse
     */
    public function deactivateWarehouse(
        string $warehouseId,
        int $tenantId,
        ?int $userId = null
    ): Warehouse {
        $id = WarehouseId::fromString($warehouseId);
        $warehouse = $this->warehouseRepository->findById($id);

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        $updatedWarehouse = $warehouse->deactivate();
        $correlationId = Str::uuid()->toString();

        $this->warehouseRepository->save($updatedWarehouse);

        $this->auditService->logEvent(
            eventType: 'warehouse_deactivated',
            context: [
                'entity_type' => 'warehouse',
                'entity_id' => $warehouseId,
                'name' => $warehouse->getName(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse deactivated', [
            'warehouse_id' => $warehouseId,
            'correlation_id' => $correlationId,
        ]);

        return $updatedWarehouse;
    }

    /**
     * Rename warehouse
     */
    public function renameWarehouse(
        string $warehouseId,
        string $newName,
        int $tenantId,
        ?int $userId = null
    ): Warehouse {
        $id = WarehouseId::fromString($warehouseId);
        $warehouse = $this->warehouseRepository->findById($id);

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        $updatedWarehouse = $warehouse->rename($newName);
        $correlationId = Str::uuid()->toString();

        $this->warehouseRepository->save($updatedWarehouse);

        $this->auditService->logEvent(
            eventType: 'warehouse_renamed',
            context: [
                'entity_type' => 'warehouse',
                'entity_id' => $warehouseId,
                'old_name' => $warehouse->getName(),
                'new_name' => $newName,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse renamed', [
            'warehouse_id' => $warehouseId,
            'old_name' => $warehouse->getName(),
            'new_name' => $newName,
            'correlation_id' => $correlationId,
        ]);

        return $updatedWarehouse;
    }

    /**
     * Change warehouse capacity
     */
    public function changeCapacity(
        string $warehouseId,
        int $newCapacity,
        int $tenantId,
        ?int $userId = null
    ): Warehouse {
        $id = WarehouseId::fromString($warehouseId);
        $warehouse = $this->warehouseRepository->findById($id);

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        $updatedWarehouse = $warehouse->changeCapacity($newCapacity);
        $correlationId = Str::uuid()->toString();

        $this->warehouseRepository->save($updatedWarehouse);

        $this->auditService->logEvent(
            eventType: 'warehouse_capacity_changed',
            context: [
                'entity_type' => 'warehouse',
                'entity_id' => $warehouseId,
                'old_capacity' => $warehouse->getCapacity(),
                'new_capacity' => $newCapacity,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse capacity changed', [
            'warehouse_id' => $warehouseId,
            'old_capacity' => $warehouse->getCapacity(),
            'new_capacity' => $newCapacity,
            'correlation_id' => $correlationId,
        ]);

        return $updatedWarehouse;
    }

    /**
     * Get warehouse by ID
     */
    public function getWarehouse(string $warehouseId): ?Warehouse
    {
        $id = WarehouseId::fromString($warehouseId);
        return $this->warehouseRepository->findById($id);
    }

    /**
     * Get all warehouses for tenant
     */
    public function getWarehousesByTenant(int $tenantId): array
    {
        return $this->warehouseRepository->findByTenantId((string) $tenantId);
    }

    /**
     * Get active warehouses
     */
    public function getActiveWarehouses(): array
    {
        return $this->warehouseRepository->findActive();
    }

    /**
     * Get warehouses by branch
     */
    public function getWarehousesByBranch(string $branchId): array
    {
        return $this->warehouseRepository->findByBranchId($branchId);
    }

    /**
     * Delete warehouse
     */
    public function deleteWarehouse(
        string $warehouseId,
        int $tenantId,
        ?int $userId = null
    ): void {
        $id = WarehouseId::fromString($warehouseId);
        $warehouse = $this->warehouseRepository->findById($id);

        if (!$warehouse) {
            throw new \InvalidArgumentException('Warehouse not found');
        }

        $correlationId = Str::uuid()->toString();

        $this->warehouseRepository->delete($id);

        $this->auditService->logEvent(
            eventType: 'warehouse_deleted',
            context: [
                'entity_type' => 'warehouse',
                'entity_id' => $warehouseId,
                'name' => $warehouse->getName(),
                'type' => $warehouse->getType()->value,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse deleted', [
            'warehouse_id' => $warehouseId,
            'name' => $warehouse->getName(),
            'correlation_id' => $correlationId,
        ]);
    }
}
