<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Warehouse\Domain\Entities\InventoryItem;
use Modules\Warehouse\Domain\ValueObjects\InventoryItemId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class LowStockAlert
{
    use Dispatchable;

    public function __construct(
        private InventoryItem $inventoryItem,
        private InventoryItemId $inventoryItemId,
        private WarehouseId $warehouseId,
        private int $currentQuantity,
        private int $minStockLevel
    ) {}

    public function getInventoryItem(): InventoryItem
    {
        return $this->inventoryItem;
    }

    public function getInventoryItemId(): InventoryItemId
    {
        return $this->inventoryItemId;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getCurrentQuantity(): int
    {
        return $this->currentQuantity;
    }

    public function getMinStockLevel(): int
    {
        return $this->minStockLevel;
    }

    public function toArray(): array
    {
        return [
            'inventory_item' => $this->inventoryItem->toArray(),
            'inventory_item_id' => $this->inventoryItemId->toString(),
            'warehouse_id' => $this->warehouseId->toString(),
            'current_quantity' => $this->currentQuantity,
            'min_stock_level' => $this->minStockLevel,
        ];
    }
}
