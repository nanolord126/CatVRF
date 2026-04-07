<?php

declare(strict_types=1);

namespace App\Domains\Inventory\DTOs;

/**
 * DTO для корректировки остатков (adjustment / stock-take).
 *
 * Используется при ручной корректировке и инвентаризации.
 */
final readonly class CreateAdjustmentDto
{
    public function __construct(
        public int     $tenantId,
        public int     $productId,
        public int     $warehouseId,
        public int     $newQuantity,
        public string  $reason,
        public string  $correlationId,
        public ?int    $businessGroupId = null,
        public ?int    $employeeId = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId:        (int) ($data['tenant_id'] ?? 0),
            productId:       (int) ($data['product_id'] ?? 0),
            warehouseId:     (int) ($data['warehouse_id'] ?? 0),
            newQuantity:     (int) ($data['new_quantity'] ?? 0),
            reason:          (string) ($data['reason'] ?? ''),
            correlationId:   (string) ($data['correlation_id'] ?? ''),
            businessGroupId: isset($data['business_group_id']) ? (int) $data['business_group_id'] : null,
            employeeId:      isset($data['employee_id']) ? (int) $data['employee_id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'product_id'        => $this->productId,
            'warehouse_id'      => $this->warehouseId,
            'new_quantity'      => $this->newQuantity,
            'reason'            => $this->reason,
            'correlation_id'    => $this->correlationId,
            'business_group_id' => $this->businessGroupId,
            'employee_id'       => $this->employeeId,
        ];
    }
}
