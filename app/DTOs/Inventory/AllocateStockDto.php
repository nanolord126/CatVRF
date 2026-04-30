<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

/**
 * Allocate Stock DTO
 *
 * Immutable data transfer object for stock allocation operations.
 * Used for allocating inventory to orders with full context tracking.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class AllocateStockDto
{
    public function __construct(
        public int $orderId,
        public string $orderType,
        public int $itemId,
        public int $quantity,
        public int $userId,
        public int $tenantId,
        public ?string $correlationId = null,
        public array $meta = []
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            orderType: $data['order_type'],
            itemId: $data['item_id'],
            quantity: $data['quantity'],
            userId: $data['user_id'],
            tenantId: $data['tenant_id'],
            correlationId: $data['correlation_id'] ?? null,
            meta: $data['meta'] ?? []
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'order_type' => $this->orderType,
            'item_id' => $this->itemId,
            'quantity' => $this->quantity,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
            'meta' => $this->meta,
        ];
    }

    /**
     * Validate DTO
     */
    public function validate(): bool
    {
        return $this->quantity > 0
            && $this->itemId > 0
            && $this->userId > 0
            && $this->tenantId > 0
            && in_array($this->orderType, ['b2b', 'b2c']);
    }
}
