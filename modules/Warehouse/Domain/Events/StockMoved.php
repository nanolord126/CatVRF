<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Warehouse\Domain\Entities\StockMovement;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class StockMoved
{
    use Dispatchable;

    public function __construct(
        private StockMovement $movement,
        private WarehouseId $warehouseId,
        private string $productSku,
        private int $quantity
    ) {}

    public function getMovement(): StockMovement
    {
        return $this->movement;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'movement' => $this->movement->toArray(),
            'warehouse_id' => $this->warehouseId->toString(),
            'product_sku' => $this->productSku,
            'quantity' => $this->quantity,
        ];
    }
}
