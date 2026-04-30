<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

/**
 * Update Stock DTO
 *
 * Immutable data transfer object for stock update operations.
 * Used for adjusting inventory quantities with version control.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class UpdateStockDto
{
    public function __construct(
        public int $itemId,
        public int $quantity,
        public int $expectedVersion,
        public string $reason,
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
            itemId: $data['item_id'],
            quantity: $data['quantity'],
            expectedVersion: $data['expected_version'],
            reason: $data['reason'],
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
            'item_id' => $this->itemId,
            'quantity' => $this->quantity,
            'expected_version' => $this->expectedVersion,
            'reason' => $this->reason,
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
        return $this->itemId > 0
            && $this->quantity >= 0
            && $this->expectedVersion >= 0
            && $this->userId > 0
            && $this->tenantId > 0
            && !empty($this->reason);
    }
}
