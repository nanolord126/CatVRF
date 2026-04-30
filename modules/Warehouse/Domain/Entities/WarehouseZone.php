<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\Enums\ZoneTypeEnum;

final readonly class WarehouseZone
{
    public function __construct(
        private ZoneId $id,
        private WarehouseId $warehouseId,
        private string $name,
        private ZoneTypeEnum $type,
        private int $capacity,
        private int $currentStock,
        private ?string $branchId = null,
        private bool $isActive = true,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        private ?\DateTimeImmutable $updatedAt = null
    ) {
        if ($this->currentStock > $this->capacity) {
            throw new \InvalidArgumentException('Current stock cannot exceed capacity');
        }
    }

    public static function create(
        WarehouseId $warehouseId,
        string $name,
        ZoneTypeEnum $type,
        int $capacity,
        ?string $branchId = null
    ): self {
        return new self(
            id: ZoneId::generate(),
            warehouseId: $warehouseId,
            name: $name,
            type: $type,
            capacity: $capacity,
            currentStock: 0,
            branchId: $branchId,
            isActive: true,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function getId(): ZoneId
    {
        return $this->id;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ZoneTypeEnum
    {
        return $this->type;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getCurrentStock(): int
    {
        return $this->currentStock;
    }

    public function getBranchId(): ?string
    {
        return $this->branchId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getUtilizationPercentage(): float
    {
        if ($this->capacity === 0) {
            return 0.0;
        }

        return ($this->currentStock / $this->capacity) * 100;
    }

    public function updateStock(int $quantity): self
    {
        $newStock = $this->currentStock + $quantity;

        if ($newStock < 0) {
            throw new \InvalidArgumentException('Stock cannot be negative');
        }

        if ($newStock > $this->capacity) {
            throw new \InvalidArgumentException('Stock cannot exceed capacity');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            name: $this->name,
            type: $this->type,
            capacity: $this->capacity,
            currentStock: $newStock,
            branchId: $this->branchId,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'warehouse_id' => $this->warehouseId->toString(),
            'name' => $this->name,
            'type' => $this->type->value,
            'capacity' => $this->capacity,
            'current_stock' => $this->currentStock,
            'branch_id' => $this->branchId,
            'is_active' => $this->isActive,
            'utilization_percentage' => $this->getUtilizationPercentage(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
