<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\DTOs;

readonly class SubscriptionItemData
{
    public function __construct(
        public int $productId,
        public ?int $variantId,
        public int $quantity,
        public float $pricePerUnit,
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        if ($pricePerUnit < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }

    public function getTotal(): float
    {
        return $this->quantity * $this->pricePerUnit;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            variantId: $data['variant_id'] ?? null,
            quantity: $data['quantity'],
            pricePerUnit: $data['price_per_unit'],
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'quantity' => $this->quantity,
            'price_per_unit' => $this->pricePerUnit,
            'total' => $this->getTotal(),
        ];
    }
}
