<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Modules\Restaurant\Domain\ValueObjects\Money;

final readonly class Ingredient
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public ?string $description,
        public string $sku,
        public string $unit, // kg, g, l, ml, pcs, etc.
        public float $currentStock,
        public float $minStock,
        public float $maxStock,
        public Money $costPerUnit,
        public bool $isActive,
        public ?int $supplierId,
        public \Carbon\CarbonImmutable $createdAt,
        public \Carbon\CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $unit,
        Money $costPerUnit,
        string $sku = '',
        float $minStock = 10.0,
        float $maxStock = 100.0,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: null,
            sku: $sku,
            unit: $unit,
            currentStock: 0.0,
            minStock: $minStock,
            maxStock: $maxStock,
            costPerUnit: $costPerUnit,
            isActive: true,
            supplierId: null,
            createdAt: now()->toImmutable(),
            updatedAt: now()->toImmutable(),
        );
    }

    public function withStock(float $stock): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            sku: $this->sku,
            unit: $this->unit,
            currentStock: $stock,
            minStock: $this->minStock,
            maxStock: $this->maxStock,
            costPerUnit: $this->costPerUnit,
            isActive: $this->isActive,
            supplierId: $this->supplierId,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function addStock(float $quantity): self
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
        return $this->withStock($this->currentStock + $quantity);
    }

    public function removeStock(float $quantity): self
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
        $newStock = $this->currentStock - $quantity;
        if ($newStock < 0) {
            throw new \InvalidArgumentException('Insufficient stock');
        }
        return $this->withStock($newStock);
    }

    public function isLowStock(): bool
    {
        return $this->currentStock <= $this->minStock;
    }

    public function isOverstocked(): bool
    {
        return $this->currentStock >= $this->maxStock;
    }

    public function getStockPercentage(): float
    {
        if ($this->maxStock === 0.0) {
            return 0.0;
        }
        return ($this->currentStock / $this->maxStock) * 100;
    }

    public function getTotalValue(): Money
    {
        return $this->costPerUnit->multiply($this->currentStock);
    }
}
