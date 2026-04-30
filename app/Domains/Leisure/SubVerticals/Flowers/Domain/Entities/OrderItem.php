<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class OrderItem
{
    public function __construct(
        public int $id,
        public int $orderId,
        public int $productId,
        public string $productName,
        public int $quantity,
        public float $unitPrice,
        public float $discountAmount,
        public float $totalPrice,
        public ?array $customizations,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $orderId,
        int $productId,
        string $productName,
        int $quantity,
        float $unitPrice,
        ?array $customizations = null,
    ): self {
        $totalPrice = $unitPrice * $quantity;

        return new self(
            id: 0,
            orderId: $orderId,
            productId: $productId,
            productName: $productName,
            quantity: $quantity,
            unitPrice: $unitPrice,
            discountAmount: 0,
            totalPrice: $totalPrice,
            customizations: $customizations,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }
}
