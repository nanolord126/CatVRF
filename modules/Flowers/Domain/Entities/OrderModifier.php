<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class OrderModifier
{
    public function __construct(
        public int $id,
        public int $orderId,
        public int $modifierId,
        public string $modifierName,
        public int $quantity,
        public float $unitPrice,
        public float $totalPrice,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $orderId,
        int $modifierId,
        string $modifierName,
        int $quantity,
        float $unitPrice,
    ): self {
        $totalPrice = $unitPrice * $quantity;

        return new self(
            id: 0,
            orderId: $orderId,
            modifierId: $modifierId,
            modifierName: $modifierName,
            quantity: $quantity,
            unitPrice: $unitPrice,
            totalPrice: $totalPrice,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }
}
