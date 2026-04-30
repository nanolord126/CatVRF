<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Warehouse\Domain\Entities\InventoryCount;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class InventoryCounted
{
    use Dispatchable;

    public function __construct(
        private InventoryCount $inventoryCount,
        private WarehouseId $warehouseId,
        private string $countNumber,
        private int $discrepanciesFound
    ) {}

    public function getInventoryCount(): InventoryCount
    {
        return $this->inventoryCount;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getCountNumber(): string
    {
        return $this->countNumber;
    }

    public function getDiscrepanciesFound(): int
    {
        return $this->discrepanciesFound;
    }

    public function toArray(): array
    {
        return [
            'inventory_count' => $this->inventoryCount->toArray(),
            'warehouse_id' => $this->warehouseId->toString(),
            'count_number' => $this->countNumber,
            'discrepancies_found' => $this->discrepanciesFound,
        ];
    }
}
