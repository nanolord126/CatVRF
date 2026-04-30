<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\DTOs;

readonly class ReturnItemData
{
    public function __construct(
        public int $orderItemId,
        public int $productId,
        public int $quantity,
        public float $pricePerUnit,
        public string $condition = 'good',
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        if ($pricePerUnit < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        if (!in_array($condition, ['good', 'spoiled', 'damaged'], true)) {
            throw new \InvalidArgumentException('Invalid item condition');
        }
    }

    public function getRefundAmount(): float
    {
        return $this->pricePerUnit * $this->quantity;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderItemId: $data['order_item_id'],
            productId: $data['product_id'],
            quantity: $data['quantity'],
            pricePerUnit: $data['price_per_unit'],
            condition: $data['condition'] ?? 'good',
        );
    }

    public function toArray(): array
    {
        return [
            'order_item_id' => $this->orderItemId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'price_per_unit' => $this->pricePerUnit,
            'refund_amount' => $this->getRefundAmount(),
            'condition' => $this->condition,
        ];
    }
}
