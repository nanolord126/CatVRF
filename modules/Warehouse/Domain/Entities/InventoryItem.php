<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\InventoryItemId;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;

final readonly class InventoryItem
{
    public function __construct(
        private InventoryItemId $id,
        private WarehouseId $warehouseId,
        private ?ZoneId $zoneId,
        private string $productSku,
        private string $productName,
        private int $quantity,
        private int $reservedQuantity,
        private ?OrderTypeEnum $orderType,
        private ?string $branchId,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
        if ($this->reservedQuantity > $this->quantity) {
            throw new \InvalidArgumentException('Reserved quantity cannot exceed total quantity');
        }
    }

    public static function create(
        WarehouseId $warehouseId,
        ?ZoneId $zoneId,
        string $productSku,
        string $productName,
        int $quantity,
        ?OrderTypeEnum $orderType = null,
        ?string $branchId = null
    ): self {
        return new self(
            id: InventoryItemId::generate(),
            warehouseId: $warehouseId,
            zoneId: $zoneId,
            productSku: $productSku,
            productName: $productName,
            quantity: $quantity,
            reservedQuantity: 0,
            orderType: $orderType,
            branchId: $branchId,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function getId(): InventoryItemId
    {
        return $this->id;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getZoneId(): ?ZoneId
    {
        return $this->zoneId;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getReservedQuantity(): int
    {
        return $this->reservedQuantity;
    }

    public function getOrderType(): ?OrderTypeEnum
    {
        return $this->orderType;
    }

    public function getBranchId(): ?string
    {
        return $this->branchId;
    }

    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->reservedQuantity;
    }

    public function isB2B(): bool
    {
        return $this->orderType === OrderTypeEnum::B2B;
    }

    public function isB2C(): bool
    {
        return $this->orderType === OrderTypeEnum::B2C;
    }

    public function isShared(): bool
    {
        return $this->orderType === null;
    }

    public function addQuantity(int $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            productSku: $this->productSku,
            productName: $this->productName,
            quantity: $this->quantity + $amount,
            reservedQuantity: $this->reservedQuantity,
            orderType: $this->orderType,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function removeQuantity(int $amount): self
    {
        $newQuantity = $this->quantity - $amount;

        if ($newQuantity < $this->reservedQuantity) {
            throw new \InvalidArgumentException('Cannot remove quantity below reserved amount');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            productSku: $this->productSku,
            productName: $this->productName,
            quantity: $newQuantity,
            reservedQuantity: $this->reservedQuantity,
            orderType: $this->orderType,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function reserveQuantity(int $amount): self
    {
        $newReserved = $this->reservedQuantity + $amount;

        if ($newReserved > $this->quantity) {
            throw new \InvalidArgumentException('Cannot reserve more than available quantity');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            productSku: $this->productSku,
            productName: $this->productName,
            quantity: $this->quantity,
            reservedQuantity: $newReserved,
            orderType: $this->orderType,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function releaseReservation(int $amount): self
    {
        $newReserved = $this->reservedQuantity - $amount;

        if ($newReserved < 0) {
            throw new \InvalidArgumentException('Cannot release more than reserved');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            productSku: $this->productSku,
            productName: $this->productName,
            quantity: $this->quantity,
            reservedQuantity: $newReserved,
            orderType: $this->orderType,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function convertToOrderType(OrderTypeEnum $newOrderType): self
    {
        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            productSku: $this->productSku,
            productName: $this->productName,
            quantity: $this->quantity,
            reservedQuantity: $this->reservedQuantity,
            orderType: $newOrderType,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function moveToZone(ZoneId $newZoneId): self
    {
        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $newZoneId,
            productSku: $this->productSku,
            productName: $this->productName,
            quantity: $this->quantity,
            reservedQuantity: $this->reservedQuantity,
            orderType: $this->orderType,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'warehouse_id' => $this->warehouseId->toString(),
            'zone_id' => $this->zoneId?->toString(),
            'product_sku' => $this->productSku,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reservedQuantity,
            'available_quantity' => $this->getAvailableQuantity(),
            'order_type' => $this->orderType?->value,
            'branch_id' => $this->branchId,
            'is_shared' => $this->isShared(),
            'is_b2b' => $this->isB2B(),
            'is_b2c' => $this->isB2C(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
