<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\InventoryItem;
use Modules\Warehouse\Domain\Entities\StockMovement;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;
use Modules\Warehouse\Domain\Enums\MovementTypeEnum;
use Modules\Warehouse\Domain\ValueObjects\OrderColor;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\StockMovementRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use App\Services\AuditService;

final readonly class WarehouseOrderTypeSwitchingService
{
    private const B2B_B2C_BRIGHTNESS_DIFFERENCE = 17;
    private const CACHE_TTL = 3600;

    public function __construct(
        private InventoryItemRepositoryInterface $inventoryItemRepository,
        private StockMovementRepositoryInterface $stockMovementRepository,
        private AuditService $auditService,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger
    ) {}

    public function switchOrderType(
        string $inventoryItemId,
        OrderTypeEnum $newOrderType,
        ?string $userId = null,
        ?string $tenantId = null
    ): InventoryItem {
        $inventoryItem = $this->inventoryItemRepository->findById($inventoryItemId);

        if (!$inventoryItem) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        $oldOrderType = $inventoryItem->getOrderType();

        if ($oldOrderType === $newOrderType) {
            return $inventoryItem;
        }

        $updatedItem = $inventoryItem->convertToOrderType($newOrderType);
        $this->inventoryItemRepository->save($updatedItem);

        $movement = StockMovement::create(
            warehouseId: $inventoryItem->getWarehouseId(),
            fromZoneId: $inventoryItem->getZoneId(),
            toZoneId: $inventoryItem->getZoneId(),
            inventoryItemId: $inventoryItem->getId(),
            productSku: $inventoryItem->getProductSku(),
            quantity: $inventoryItem->getQuantity(),
            movementType: MovementTypeEnum::CONVERSION,
            orderType: $newOrderType,
            branchId: $inventoryItem->getBranchId(),
            reason: sprintf(
                'Order type converted from %s to %s',
                $oldOrderType?->value ?? 'shared',
                $newOrderType->value
            )
        );

        $this->stockMovementRepository->save($movement);

        $this->auditService->logAction(
            action: 'warehouse.order_type_switched',
            entityType: 'InventoryItem',
            entityId: $inventoryItemId,
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'old_order_type' => $oldOrderType?->value ?? 'shared',
                'new_order_type' => $newOrderType->value,
                'quantity' => $inventoryItem->getQuantity(),
                'product_sku' => $inventoryItem->getProductSku(),
            ]
        );

        $this->invalidateCache($inventoryItemId);

        return $updatedItem;
    }

    public function convertSharedToB2B(
        string $inventoryItemId,
        int $quantity,
        ?string $userId = null,
        ?string $tenantId = null
    ): InventoryItem {
        $inventoryItem = $this->inventoryItemRepository->findById($inventoryItemId);

        if (!$inventoryItem) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        if (!$inventoryItem->isShared()) {
            throw new \InvalidArgumentException('Item is not shared, cannot convert');
        }

        if ($inventoryItem->getAvailableQuantity() < $quantity) {
            throw new \InvalidArgumentException('Insufficient available quantity');
        }

        $updatedItem = $inventoryItem->convertToOrderType(OrderTypeEnum::B2B);
        $this->inventoryItemRepository->save($updatedItem);

        $movement = StockMovement::create(
            warehouseId: $inventoryItem->getWarehouseId(),
            fromZoneId: $inventoryItem->getZoneId(),
            toZoneId: $inventoryItem->getZoneId(),
            inventoryItemId: $inventoryItem->getId(),
            productSku: $inventoryItem->getProductSku(),
            quantity: $quantity,
            movementType: MovementTypeEnum::CONVERSION,
            orderType: OrderTypeEnum::B2B,
            branchId: $inventoryItem->getBranchId(),
            reason: 'Shared inventory converted to B2B'
        );

        $this->stockMovementRepository->save($movement);

        $this->auditService->logAction(
            action: 'warehouse.shared_to_b2b',
            entityType: 'InventoryItem',
            entityId: $inventoryItemId,
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'quantity' => $quantity,
                'product_sku' => $inventoryItem->getProductSku(),
            ]
        );

        $this->invalidateCache($inventoryItemId);

        return $updatedItem;
    }

    public function convertSharedToB2C(
        string $inventoryItemId,
        int $quantity,
        ?string $userId = null,
        ?string $tenantId = null
    ): InventoryItem {
        $inventoryItem = $this->inventoryItemRepository->findById($inventoryItemId);

        if (!$inventoryItem) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        if (!$inventoryItem->isShared()) {
            throw new \InvalidArgumentException('Item is not shared, cannot convert');
        }

        if ($inventoryItem->getAvailableQuantity() < $quantity) {
            throw new \InvalidArgumentException('Insufficient available quantity');
        }

        $updatedItem = $inventoryItem->convertToOrderType(OrderTypeEnum::B2C);
        $this->inventoryItemRepository->save($updatedItem);

        $movement = StockMovement::create(
            warehouseId: $inventoryItem->getWarehouseId(),
            fromZoneId: $inventoryItem->getZoneId(),
            toZoneId: $inventoryItem->getZoneId(),
            inventoryItemId: $inventoryItem->getId(),
            productSku: $inventoryItem->getProductSku(),
            quantity: $quantity,
            movementType: MovementTypeEnum::CONVERSION,
            orderType: OrderTypeEnum::B2C,
            branchId: $inventoryItem->getBranchId(),
            reason: 'Shared inventory converted to B2C'
        );

        $this->stockMovementRepository->save($movement);

        $this->auditService->logAction(
            action: 'warehouse.shared_to_b2c',
            entityType: 'InventoryItem',
            entityId: $inventoryItemId,
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'quantity' => $quantity,
                'product_sku' => $inventoryItem->getProductSku(),
            ]
        );

        $this->invalidateCache($inventoryItemId);

        return $updatedItem;
    }

    public function getOrderColor(OrderTypeEnum $orderType): OrderColor
    {
        $baseColor = $orderType->getDefaultColor();

        if ($orderType === OrderTypeEnum::B2C) {
            return OrderColor::withBrightnessAdjustment(
                $baseColor,
                $orderType,
                self::B2B_B2C_BRIGHTNESS_DIFFERENCE
            );
        }

        return OrderColor::forOrderType($orderType);
    }

    public function getColorDifference(
        OrderTypeEnum $orderType1,
        OrderTypeEnum $orderType2
    ): int {
        $color1 = $this->getOrderColor($orderType1);
        $color2 = $this->getOrderColor($orderType2);

        return $color1->getBrightnessDifference($color2);
    }

    public function validateColorDifference(): bool
    {
        $b2bColor = $this->getOrderColor(OrderTypeEnum::B2B);
        $b2cColor = $this->getOrderColor(OrderTypeEnum::B2C);

        $difference = $b2cColor->getBrightnessDifference($b2bColor);

        return $difference >= self::B2B_B2C_BRIGHTNESS_DIFFERENCE;
    }

    public function getOrderColorForDisplay(OrderTypeEnum $orderType): array
    {
        $color = $this->getOrderColor($orderType);

        return [
            'hex_color' => $color->getHexColor(),
            'order_type' => $orderType->value,
            'label' => $orderType->getLabel(),
            'brightness_level' => $color->getBrightnessLevel(),
            'is_b2c' => $orderType === OrderTypeEnum::B2C,
            'brightness_adjustment' => $orderType === OrderTypeEnum::B2C ? self::B2B_B2C_BRIGHTNESS_DIFFERENCE : 0,
        ];
    }

    public function getInventoryByOrderType(
        WarehouseId $warehouseId,
        OrderTypeEnum $orderType,
        ?string $branchId = null
    ): array {
        $cacheKey = $this->getCacheKey($warehouseId->toString(), $orderType->value, $branchId);

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($warehouseId, $orderType, $branchId) {
            return $this->inventoryItemRepository->findByWarehouseAndOrderType($warehouseId, $orderType, $branchId);
        });
    }

    public function getSharedInventory(WarehouseId $warehouseId, ?string $branchId = null): array
    {
        $cacheKey = $this->getCacheKey($warehouseId->toString(), 'shared', $branchId);

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($warehouseId, $branchId) {
            return $this->inventoryItemRepository->findSharedByWarehouse($warehouseId, $branchId);
        });
    }

    public function getInventoryWithColorMapping(
        WarehouseId $warehouseId,
        ?string $branchId = null
    ): array {
        $items = $this->inventoryItemRepository->findByWarehouse($warehouseId, $branchId);

        return array_map(function (InventoryItem $item) {
            $orderType = $item->getOrderType() ?? OrderTypeEnum::B2B;
            $color = $this->getOrderColor($orderType);

            return [
                'item' => $item->toArray(),
                'color' => $color->toArray(),
                'display_color' => $this->getOrderColorForDisplay($orderType),
            ];
        }, $items);
    }

    public function batchConvertOrderType(
        array $inventoryItemIds,
        OrderTypeEnum $newOrderType,
        ?string $userId = null,
        ?string $tenantId = null
    ): array {
        $results = [];

        foreach ($inventoryItemIds as $itemId) {
            try {
                $updatedItem = $this->switchOrderType($itemId, $newOrderType, $userId, $tenantId);
                $results[$itemId] = [
                    'success' => true,
                    'item' => $updatedItem->toArray(),
                ];
            } catch (\Exception $e) {
                $results[$itemId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function getCacheKey(string $warehouseId, string $orderType, ?string $branchId): string
    {
        $branchPart = $branchId ? "_{$branchId}" : '';

        return "warehouse_inventory_{$warehouseId}_{$orderType}{$branchPart}";
    }

    private function invalidateCache(string $inventoryItemId): void
    {
        $item = $this->inventoryItemRepository->findById($inventoryItemId);

        if (!$item) {
            return;
        }

        $warehouseId = $item->getWarehouseId()->toString();
        $branchId = $item->getBranchId();

        $cacheKeys = [
            $this->getCacheKey($warehouseId, 'b2b', $branchId),
            $this->getCacheKey($warehouseId, 'b2c', $branchId),
            $this->getCacheKey($warehouseId, 'shared', $branchId),
        ];

        foreach ($cacheKeys as $key) {
            $this->cache->forget($key);
        }
    }
}
