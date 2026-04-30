<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Enums\WarehouseTypeEnum;

final readonly class Warehouse
{
    public function __construct(
        private WarehouseId $id,
        private string $name,
        private string $address,
        private ?string $branchId,
        private WarehouseTypeEnum $type,
        private int $capacity,
        private int $currentStock,
        private bool $isActive,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
        if ($this->currentStock > $this->capacity) {
            throw new \InvalidArgumentException('Current stock cannot exceed capacity');
        }
    }

    public static function create(
        string $name,
        string $address,
        ?string $branchId,
        WarehouseTypeEnum $type,
        int $capacity
    ): self {
        return new self(
            id: WarehouseId::generate(),
            name: $name,
            address: $address,
            branchId: $branchId,
            type: $type,
            capacity: $capacity,
            currentStock: 0,
            isActive: true,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function getId(): WarehouseId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getBranchId(): ?string
    {
        return $this->branchId;
    }

    public function getType(): WarehouseTypeEnum
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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
            name: $this->name,
            address: $this->address,
            branchId: $this->branchId,
            type: $this->type,
            capacity: $this->capacity,
            currentStock: $newStock,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            address: $this->address,
            branchId: $this->branchId,
            type: $this->type,
            capacity: $this->capacity,
            currentStock: $this->currentStock,
            isActive: true,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            address: $this->address,
            branchId: $this->branchId,
            type: $this->type,
            capacity: $this->capacity,
            currentStock: $this->currentStock,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function rename(string $newName): self
    {
        return new self(
            id: $this->id,
            name: $newName,
            address: $this->address,
            branchId: $this->branchId,
            type: $this->type,
            capacity: $this->capacity,
            currentStock: $this->currentStock,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function changeCapacity(int $newCapacity): self
    {
        if ($newCapacity < $this->currentStock) {
            throw new \InvalidArgumentException('New capacity cannot be less than current stock');
        }

        return new self(
            id: $this->id,
            name: $this->name,
            address: $this->address,
            branchId: $this->branchId,
            type: $this->type,
            capacity: $newCapacity,
            currentStock: $this->currentStock,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'name' => $this->name,
            'address' => $this->address,
            'branch_id' => $this->branchId,
            'type' => $this->type->value,
            'capacity' => $this->capacity,
            'current_stock' => $this->currentStock,
            'is_active' => $this->isActive,
            'utilization_percentage' => $this->getUtilizationPercentage(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
