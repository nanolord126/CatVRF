<?php

declare(strict_types=1);

namespace App\Domains\Inventory\DTOs;

/**
 * DTO для создания движения остатков.
 *
 * Используется InventoryService при любом изменении stock.
 */
final readonly class CreateStockMovementDto
{
    public function __construct(
        public int     $tenantId,
        public int     $inventoryId,
        public int     $warehouseId,
        public string  $type,
        public int     $quantity,
        public string  $sourceType,
        public string  $correlationId,
        public ?int    $sourceId = null,
        public ?array  $metadata = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId:      (int) ($data['tenant_id'] ?? 0),
            inventoryId:   (int) ($data['inventory_id'] ?? 0),
            warehouseId:   (int) ($data['warehouse_id'] ?? 0),
            type:          (string) ($data['type'] ?? 'adjustment'),
            quantity:      (int) ($data['quantity'] ?? 0),
            sourceType:    (string) ($data['source_type'] ?? 'manual'),
            correlationId: (string) ($data['correlation_id'] ?? ''),
            sourceId:      isset($data['source_id']) ? (int) $data['source_id'] : null,
            metadata:      $data['metadata'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tenant_id'      => $this->tenantId,
            'inventory_id'   => $this->inventoryId,
            'warehouse_id'   => $this->warehouseId,
            'type'           => $this->type,
            'quantity'        => $this->quantity,
            'source_type'    => $this->sourceType,
            'correlation_id' => $this->correlationId,
            'source_id'      => $this->sourceId,
            'metadata'       => $this->metadata,
        ];
    }
}
