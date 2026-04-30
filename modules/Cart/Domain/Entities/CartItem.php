<?php

declare(strict_types=1);

namespace Modules\Cart\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class CartItem
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $cartId,
        public int $productId,
        public int $quantity,
        public int $priceAtAdd,
        public int $currentPrice,
        public ?array $metadata,
        public string $correlationId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function getEffectivePrice(): int
    {
        return max($this->priceAtAdd, $this->currentPrice);
    }

    public function getTotal(): int
    {
        return $this->getEffectivePrice() * $this->quantity;
    }
}
