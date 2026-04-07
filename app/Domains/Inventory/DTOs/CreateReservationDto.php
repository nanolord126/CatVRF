<?php

declare(strict_types=1);

namespace App\Domains\Inventory\DTOs;

use Carbon\Carbon;

/**
 * DTO для резервирования товара.
 *
 * Используется InventoryService::reserve().
 * B2C: expires_at = Carbon::now() + 20 мин.
 * B2B: expires_at = Carbon::now() + payment_term_days.
 */
final readonly class CreateReservationDto
{
    public function __construct(
        public int     $tenantId,
        public int     $productId,
        public int     $warehouseId,
        public int     $quantity,
        public string  $sourceType,
        public int     $sourceId,
        public string  $correlationId,
        public ?int    $businessGroupId = null,
        public ?int    $cartId = null,
        public ?int    $orderId = null,
        public ?string $expiresAt = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId:        (int) ($data['tenant_id'] ?? 0),
            productId:       (int) ($data['product_id'] ?? 0),
            warehouseId:     (int) ($data['warehouse_id'] ?? 0),
            quantity:        (int) ($data['quantity'] ?? 0),
            sourceType:      (string) ($data['source_type'] ?? 'cart'),
            sourceId:        (int) ($data['source_id'] ?? 0),
            correlationId:   (string) ($data['correlation_id'] ?? ''),
            businessGroupId: isset($data['business_group_id']) ? (int) $data['business_group_id'] : null,
            cartId:          isset($data['cart_id']) ? (int) $data['cart_id'] : null,
            orderId:         isset($data['order_id']) ? (int) $data['order_id'] : null,
            expiresAt:       $data['expires_at'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'product_id'        => $this->productId,
            'warehouse_id'      => $this->warehouseId,
            'quantity'          => $this->quantity,
            'source_type'       => $this->sourceType,
            'source_id'         => $this->sourceId,
            'correlation_id'    => $this->correlationId,
            'business_group_id' => $this->businessGroupId,
            'cart_id'           => $this->cartId,
            'order_id'          => $this->orderId,
            'expires_at'        => $this->expiresAt,
        ];
    }
}
