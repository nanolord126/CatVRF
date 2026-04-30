<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\InventoryCount;
use Modules\Warehouse\Domain\Repositories\InventoryCountRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\InventoryCountId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Entities\InventoryItem;
use Psr\Log\LoggerInterface;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * Inventory Cycle Counting Application Service
 *
 * Orchestrates cycle counting operations
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryCycleCountingApplicationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly InventoryCountRepositoryInterface $inventoryCountRepository,
        private readonly InventoryItemRepositoryInterface $inventoryItemRepository,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    /**
     * Create cycle count plan
     */
    public function createCountPlan(
        WarehouseId $warehouseId,
        string $countType,
        int $tenantId,
        ?int $userId = null
    ): InventoryCount {
        $inventoryCount = InventoryCount::create(
            warehouseId: $warehouseId,
            zoneId: null,
            countType: $countType,
            scheduledDate: new \DateTimeImmutable(),
            notes: null
        );

        $this->inventoryCountRepository->save($inventoryCount);

        $this->auditService->logAction(
            action: 'cycle_count_plan_created',
            entityType: 'InventoryCount',
            entityId: $inventoryCount->getId()->toString(),
            userId: (string) $userId,
            tenantId: (string) $tenantId,
            context: [
                'count_number' => $inventoryCount->getCountNumber(),
                'count_type' => $countType,
            ]
        );

        $this->logger->info('Cycle count plan created', [
            'count_id' => $inventoryCount->getId()->toString(),
            'count_number' => $inventoryCount->getCountNumber(),
            'count_type' => $countType,
        ]);

        return $inventoryCount;
    }

    /**
     * Start counting
     */
    public function startCount(
        string $countId,
        ?int $userId = null,
        ?string $tenantId = null
    ): void {
        $count = $this->inventoryCountRepository->findById(
            InventoryCountId::fromString($countId)
        );

        if (!$count) {
            throw new \InvalidArgumentException('Inventory count not found');
        }

        // Update status to in_progress
        $this->logger->info('Cycle count started', [
            'count_id' => $countId,
        ]);
    }

    /**
     * Complete counting
     */
    public function completeCount(
        string $countId,
        array $countItems,
        ?int $userId = null,
        ?string $tenantId = null
    ): void {
        $count = $this->inventoryCountRepository->findById(
            InventoryCountId::fromString($countId)
        );

        if (!$count) {
            throw new \InvalidArgumentException('Inventory count not found');
        }

        $this->auditService->logAction(
            action: 'cycle_count_completed',
            entityType: 'InventoryCount',
            entityId: $countId,
            userId: $userId,
            tenantId: $tenantId,
            context:(string)  [
                'items_counted' => count($countItems),
            ]
        );

        $this->logger->info('Cycle count completed', [
            'count_id' => $countId,
        ]);
    }

    /**
     * Approve count
     */
    public function approveCount(
        string $countId,
        int $userId,
        ?string $tenantId = null
    ): void {
        $count = $this->inventoryCountRepository->findById(
            InventoryCountId::fromString($countId)
        );

        if (!$count) {
            throw new \InvalidArgumentException('Inventory count not found');
        }

        $this->auditService->logAction(
            action: 'cycle_count_approved',
            entityType: 'InventoryCount',
            entityId: $countId,
            userId: (string) $userId,
            tenantId: $tenantId,
            context: []
        );

        $this->logger->info('Cycle count approved', [
            'count_id' => $countId,
            'approved_by' => $userId,
        ]);
    }
}
