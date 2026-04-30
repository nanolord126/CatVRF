<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

/**
 * Create Transfer DTO
 *
 * Immutable data transfer object for warehouse transfer operations.
 * Used for creating stock transfers between warehouses.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class CreateTransferDto
{
    public function __construct(
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public array $items, // array of ['item_id' => int, 'quantity' => int]
        public string $reason,
        public int $userId,
        public int $tenantId,
        public ?string $correlationId = null,
        public ?int $expectedDeliveryDate = null
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fromWarehouseId: $data['from_warehouse_id'],
            toWarehouseId: $data['to_warehouse_id'],
            items: $data['items'],
            reason: $data['reason'],
            userId: $data['user_id'],
            tenantId: $data['tenant_id'],
            correlationId: $data['correlation_id'] ?? null,
            expectedDeliveryDate: $data['expected_delivery_date'] ?? null
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'from_warehouse_id' => $this->fromWarehouseId,
            'to_warehouse_id' => $this->toWarehouseId,
            'items' => $this->items,
            'reason' => $this->reason,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
            'expected_delivery_date' => $this->expectedDeliveryDate,
        ];
    }

    /**
     * Get total quantity to transfer
     */
    public function getTotalQuantity(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }

    /**
     * Validate DTO
     */
    public function validate(): bool
    {
        if ($this->fromWarehouseId === $this->toWarehouseId) {
            return false;
        }

        if (empty($this->items)) {
            return false;
        }

        foreach ($this->items as $item) {
            if (!isset($item['item_id']) || !isset($item['quantity']) || $item['quantity'] <= 0) {
                return false;
            }
        }

        return $this->fromWarehouseId > 0
            && $this->toWarehouseId > 0
            && $this->userId > 0
            && $this->tenantId > 0
            && !empty($this->reason);
    }
}
