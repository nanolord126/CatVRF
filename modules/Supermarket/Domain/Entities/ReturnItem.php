<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Entities;

use Modules\Supermarket\Domain\ValueObjects\Money;

final readonly class ReturnItem
{
    private function __construct(
        public int $id,
        public int $returnId,
        public int $orderItemId,
        public int $productId,
        public int $quantity,
        public Money $pricePerUnit,
        public Money $refundAmount,
        public string $condition,
    ) {}

    public static function create(
        int $returnId,
        int $orderItemId,
        int $productId,
        int $quantity,
        Money $pricePerUnit,
        string $condition = 'good',
    ): self {
        $refundAmount = $pricePerUnit->multiply($quantity);

        return new self(
            id: 0,
            returnId: $returnId,
            orderItemId: $orderItemId,
            productId: $productId,
            quantity: $quantity,
            pricePerUnit: $pricePerUnit,
            refundAmount: $refundAmount,
            condition: $condition,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            returnId: $data['return_id'],
            orderItemId: $data['order_item_id'],
            productId: $data['product_id'],
            quantity: $data['quantity'],
            pricePerUnit: Money::fromFloat($data['price_per_unit']),
            refundAmount: Money::fromFloat($data['refund_amount']),
            condition: $data['condition'],
        );
    }

    public function updateQuantity(int $newQuantity): self
    {
        return new self(
            id: $this->id,
            returnId: $this->returnId,
            orderItemId: $this->orderItemId,
            productId: $this->productId,
            quantity: $newQuantity,
            pricePerUnit: $this->pricePerUnit,
            refundAmount: $this->pricePerUnit->multiply($newQuantity),
            condition: $this->condition,
        );
    }

    public function updateRefundAmount(Money $newAmount): self
    {
        return new self(
            id: $this->id,
            returnId: $this->returnId,
            orderItemId: $this->orderItemId,
            productId: $this->productId,
            quantity: $this->quantity,
            pricePerUnit: $this->pricePerUnit,
            refundAmount: $newAmount,
            condition: $this->condition,
        );
    }

    public function isSpoiled(): bool
    {
        return $this->condition === 'spoiled';
    }

    public function isDamaged(): bool
    {
        return $this->condition === 'damaged';
    }

    public function isGood(): bool
    {
        return $this->condition === 'good';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'return_id' => $this->returnId,
            'order_item_id' => $this->orderItemId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'price_per_unit' => $this->pricePerUnit->toArray(),
            'refund_amount' => $this->refundAmount->toArray(),
            'condition' => $this->condition,
        ];
    }
}
