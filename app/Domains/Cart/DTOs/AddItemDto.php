<?php declare(strict_types=1);

namespace App\Domains\Cart\DTOs;

final readonly class AddItemDto
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public int $sellerId,
        public int $productId,
        public int $quantity,
        public int $currentPrice,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            sellerId: $data['seller_id'],
            productId: $data['product_id'],
            quantity: $data['quantity'],
            currentPrice: $data['current_price'],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'seller_id' => $this->sellerId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'current_price' => $this->currentPrice,
        ];
    }
}
