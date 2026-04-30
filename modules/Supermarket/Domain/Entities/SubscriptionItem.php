<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Entities;

use Modules\Supermarket\Domain\ValueObjects\Money;

final readonly class SubscriptionItem
{
    private function __construct(
        public int $id,
        public int $subscriptionId,
        public int $productId,
        public ?int $variantId,
        public int $quantity,
        public Money $pricePerUnitAtCreation,
    ) {}

    public static function create(
        int $subscriptionId,
        int $productId,
        ?int $variantId,
        int $quantity,
        Money $pricePerUnit,
    ): self {
        return new self(
            id: 0,
            subscriptionId: $subscriptionId,
            productId: $productId,
            variantId: $variantId,
            quantity: $quantity,
            pricePerUnitAtCreation: $pricePerUnit,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            subscriptionId: $data['subscription_id'],
            productId: $data['product_id'],
            variantId: $data['variant_id'] ?? null,
            quantity: $data['quantity'],
            pricePerUnitAtCreation: Money::fromFloat($data['price_per_unit_at_creation']),
        );
    }

    public function getTotalPrice(): Money
    {
        return $this->pricePerUnitAtCreation->multiply($this->quantity);
    }

    public function updateQuantity(int $newQuantity): self
    {
        return new self(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            productId: $this->productId,
            variantId: $this->variantId,
            quantity: $newQuantity,
            pricePerUnitAtCreation: $this->pricePerUnitAtCreation,
        );
    }

    public function updatePrice(Money $newPrice): self
    {
        return new self(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            productId: $this->productId,
            variantId: $this->variantId,
            quantity: $this->quantity,
            pricePerUnitAtCreation: $newPrice,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscriptionId,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'quantity' => $this->quantity,
            'price_per_unit_at_creation' => $this->pricePerUnitAtCreation->toArray(),
            'total_price' => $this->getTotalPrice()->toArray(),
        ];
    }
}
