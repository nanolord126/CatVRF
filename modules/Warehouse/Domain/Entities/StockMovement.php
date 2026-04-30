<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\InventoryItemId;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;
use Modules\Warehouse\Domain\Enums\MovementTypeEnum;

final readonly class StockMovement
{
    public function __construct(
        private string $id,
        private WarehouseId $warehouseId,
        private ?ZoneId $fromZoneId,
        private ?ZoneId $toZoneId,
        private InventoryItemId $inventoryItemId,
        private string $productSku,
        private int $quantity,
        private MovementTypeEnum $movementType,
        private ?OrderTypeEnum $orderType,
        private ?string $orderId,
        private ?string $branchId,
        private ?string $reason,
        private \DateTimeImmutable $createdAt
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    }

    public static function create(
        WarehouseId $warehouseId,
        ?ZoneId $fromZoneId,
        ?ZoneId $toZoneId,
        InventoryItemId $inventoryItemId,
        string $productSku,
        int $quantity,
        MovementTypeEnum $movementType,
        ?OrderTypeEnum $orderType = null,
        ?string $orderId = null,
        ?string $branchId = null,
        ?string $reason = null
    ): self {
        return new self(
            id: \Ramsey\Uuid\Uuid::uuid4()->toString(),
            warehouseId: $warehouseId,
            fromZoneId: $fromZoneId,
            toZoneId: $toZoneId,
            inventoryItemId: $inventoryItemId,
            productSku: $productSku,
            quantity: $quantity,
            movementType: $movementType,
            orderType: $orderType,
            orderId: $orderId,
            branchId: $branchId,
            reason: $reason,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getFromZoneId(): ?ZoneId
    {
        return $this->fromZoneId;
    }

    public function getToZoneId(): ?ZoneId
    {
        return $this->toZoneId;
    }

    public function getInventoryItemId(): InventoryItemId
    {
        return $this->inventoryItemId;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getMovementType(): MovementTypeEnum
    {
        return $this->movementType;
    }

    public function getOrderType(): ?OrderTypeEnum
    {
        return $this->orderType;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getBranchId(): ?string
    {
        return $this->branchId;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isB2B(): bool
    {
        return $this->orderType === OrderTypeEnum::B2B;
    }

    public function isB2C(): bool
    {
        return $this->orderType === OrderTypeEnum::B2C;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouseId->toString(),
            'from_zone_id' => $this->fromZoneId?->toString(),
            'to_zone_id' => $this->toZoneId?->toString(),
            'inventory_item_id' => $this->inventoryItemId->toString(),
            'product_sku' => $this->productSku,
            'quantity' => $this->quantity,
            'movement_type' => $this->movementType->value,
            'order_type' => $this->orderType?->value,
            'order_id' => $this->orderId,
            'branch_id' => $this->branchId,
            'reason' => $this->reason,
            'is_b2b' => $this->isB2B(),
            'is_b2c' => $this->isB2C(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
