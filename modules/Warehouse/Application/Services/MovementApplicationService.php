<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\StockMovement;
use Modules\Warehouse\Domain\Repositories\StockMovementRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\InventoryItemId;
use Modules\Warehouse\Domain\Enums\MovementTypeEnum;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;
use Psr\Log\LoggerInterface;
use App\Services\Security\AuditService;
use Illuminate\Support\Str;

/**
 * Movement Application Service - Production Layer
 *
 * Orchestrates stock movement operations (physical movements between zones)
 * Separate from Inventory (stock counting process)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class MovementApplicationService
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movementRepository,
        private readonly InventoryItemRepositoryInterface $inventoryItemRepository,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    /**
     * Record a stock movement
     */
    public function recordMovement(
        string $warehouseId,
        ?string $fromZoneId,
        ?string $toZoneId,
        string $inventoryItemId,
        string $productSku,
        int $quantity,
        MovementTypeEnum $movementType,
        int $tenantId,
        ?OrderTypeEnum $orderType = null,
        ?string $orderId = null,
        ?string $branchId = null,
        ?string $reason = null,
        ?int $userId = null
    ): StockMovement {
        $inventoryItem = $this->inventoryItemRepository->findById($inventoryItemId);

        if (!$inventoryItem) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        $movement = StockMovement::create(
            warehouseId: WarehouseId::fromString($warehouseId),
            fromZoneId: $fromZoneId ? ZoneId::fromString($fromZoneId) : null,
            toZoneId: $toZoneId ? ZoneId::fromString($toZoneId) : null,
            inventoryItemId: InventoryItemId::fromString($inventoryItemId),
            productSku: $productSku,
            quantity: $quantity,
            movementType: $movementType,
            orderType: $orderType,
            orderId: $orderId,
            branchId: $branchId,
            reason: $reason
        );

        $correlationId = Str::uuid()->toString();

        $this->movementRepository->save($movement);

        $this->auditService->logEvent(
            eventType: 'warehouse_stock_movement',
            context: [
                'entity_type' => 'stock_movement',
                'entity_id' => $movement->getId(),
                'warehouse_id' => $warehouseId,
                'from_zone_id' => $fromZoneId,
                'to_zone_id' => $toZoneId,
                'inventory_item_id' => $inventoryItemId,
                'product_sku' => $productSku,
                'quantity' => $quantity,
                'movement_type' => $movementType->value,
                'order_type' => $orderType?->value,
                'order_id' => $orderId,
                'branch_id' => $branchId,
                'reason' => $reason,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Stock movement recorded', [
            'movement_id' => $movement->getId(),
            'warehouse_id' => $warehouseId,
            'product_sku' => $productSku,
            'quantity' => $quantity,
            'movement_type' => $movementType->value,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $movement;
    }

    /**
     * Get movement by ID
     */
    public function getMovement(string $movementId): ?StockMovement
    {
        return $this->movementRepository->findById($movementId);
    }

    /**
     * Get movements by warehouse
     */
    public function getMovementsByWarehouse(string $warehouseId, ?string $branchId = null): array
    {
        $id = WarehouseId::fromString($warehouseId);
        return $this->movementRepository->findByWarehouse($id, $branchId);
    }

    /**
     * Get movements by order type
     */
    public function getMovementsByOrderType(OrderTypeEnum $orderType, ?string $branchId = null): array
    {
        return $this->movementRepository->findByOrderType($orderType, $branchId);
    }

    /**
     * Get movements by movement type
     */
    public function getMovementsByMovementType(MovementTypeEnum $movementType, ?string $branchId = null): array
    {
        return $this->movementRepository->findByMovementType($movementType, $branchId);
    }

    /**
     * Get movements by inventory item
     */
    public function getMovementsByInventoryItem(string $inventoryItemId): array
    {
        return $this->movementRepository->findByInventoryItem($inventoryItemId);
    }

    /**
     * Get movements by date range
     */
    public function getMovementsByDateRange(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        ?string $branchId = null
    ): array {
        return $this->movementRepository->findByDateRange($from, $to, $branchId);
    }
}
