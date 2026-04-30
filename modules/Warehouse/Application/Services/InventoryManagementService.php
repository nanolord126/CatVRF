<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\InventoryItem;
use Modules\Warehouse\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\StockMovementRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Entities\StockMovement;
use Modules\Warehouse\Domain\Enums\MovementTypeEnum;
use Psr\Log\LoggerInterface;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * Inventory Management Service - Application Layer
 *
 * Orchestrates inventory operations using Domain Services and Repositories
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryManagementService
{
    use WithAuditLogging;

    public function __construct(
        private readonly InventoryItemRepositoryInterface $inventoryItemRepository,
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    /**
     * Add stock to inventory
     */
    public function addStock(
        string $inventoryItemId,
        int $quantity,
        string $reason,
        ?string $userId = null,
        ?string $tenantId = null
    ): InventoryItem {
        $item = $this->inventoryItemRepository->findById($inventoryItemId);
        
        if (!$item) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        $updatedItem = $item->addQuantity($quantity);
        $this->inventoryItemRepository->save($updatedItem);

        $movement = StockMovement::create(
            warehouseId: $updatedItem->getWarehouseId(),
            fromZoneId: null,
            toZoneId: $updatedItem->getZoneId(),
            inventoryItemId: $updatedItem->getId(),
            productSku: $updatedItem->getProductSku(),
            quantity: $quantity,
            movementType: MovementTypeEnum::RECEIPT,
            orderType: null,
            orderId: null,
            branchId: $updatedItem->getBranchId(),
            reason: $reason
        );

        $this->stockMovementRepository->save($movement);

        $this->auditService->logAction(
            action: 'inventory.stock_added',
            entityType: 'InventoryItem',
            entityId: $inventoryItemId,
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'quantity' => $quantity,
                'product_sku' => $updatedItem->getProductSku(),
                'reason' => $reason,
            ]
        );

        $this->logger->info('Stock added to inventory', [
            'item_id' => $inventoryItemId,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);

        return $updatedItem;
    }

    /**
     * Remove stock from inventory
     */
    public function removeStock(
        string $inventoryItemId,
        int $quantity,
        string $reason,
        ?string $userId = null,
        ?string $tenantId = null
    ): InventoryItem {
        $item = $this->inventoryItemRepository->findById($inventoryItemId);
        
        if (!$item) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        if ($item->getQuantity() < $quantity) {
            throw new \InvalidArgumentException('Insufficient stock');
        }

        $updatedItem = $item->removeQuantity($quantity);
        $this->inventoryItemRepository->save($updatedItem);

        $movement = StockMovement::create(
            warehouseId: $updatedItem->getWarehouseId(),
            fromZoneId: $updatedItem->getZoneId(),
            toZoneId: null,
            inventoryItemId: $updatedItem->getId(),
            productSku: $updatedItem->getProductSku(),
            quantity: $quantity,
            movementType: MovementTypeEnum::SHIPMENT,
            orderType: null,
            orderId: null,
            branchId: $updatedItem->getBranchId(),
            reason: $reason
        );

        $this->stockMovementRepository->save($movement);

        $this->auditService->logAction(
            action: 'inventory.stock_removed',
            entityType: 'InventoryItem',
            entityId: $inventoryItemId,
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'quantity' => $quantity,
                'product_sku' => $updatedItem->getProductSku(),
                'reason' => $reason,
            ]
        );

        $this->logger->info('Stock removed from inventory', [
            'item_id' => $inventoryItemId,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);

        return $updatedItem;
    }

    /**
     * Transfer stock between zones
     */
    public function transferStock(
        string $inventoryItemId,
        string $fromZoneId,
        string $toZoneId,
        int $quantity,
        string $reason,
        ?string $userId = null,
        ?string $tenantId = null
    ): void {
        $item = $this->inventoryItemRepository->findById($inventoryItemId);
        
        if (!$item) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        $movement = StockMovement::create(
            warehouseId: $item->getWarehouseId(),
            fromZoneId: $item->getZoneId(),
            toZoneId: $item->getZoneId(),
            inventoryItemId: $item->getId(),
            productSku: $item->getProductSku(),
            quantity: $quantity,
            movementType: MovementTypeEnum::TRANSFER,
            orderType: null,
            orderId: null,
            branchId: $item->getBranchId(),
            reason: $reason
        );

        $this->stockMovementRepository->save($movement);

        $this->auditService->logAction(
            action: 'inventory.stock_transferred',
            entityType: 'InventoryItem',
            entityId: $inventoryItemId,
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'from_zone' => $fromZoneId,
                'to_zone' => $toZoneId,
                'quantity' => $quantity,
                'product_sku' => $item->getProductSku(),
                'reason' => $reason,
            ]
        );

        $this->logger->info('Stock transferred between zones', [
            'item_id' => $inventoryItemId,
            'from_zone' => $fromZoneId,
            'to_zone' => $toZoneId,
            'quantity' => $quantity,
        ]);
    }
}
