<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class WarehouseCreated
{
    use Dispatchable;

    public function __construct(
        private Warehouse $warehouse,
        private WarehouseId $warehouseId,
        private string $name
    ) {}

    public function getWarehouse(): Warehouse
    {
        return $this->warehouse;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'warehouse' => $this->warehouse->toArray(),
            'warehouse_id' => $this->warehouseId->toString(),
            'name' => $this->name,
        ];
    }
}
