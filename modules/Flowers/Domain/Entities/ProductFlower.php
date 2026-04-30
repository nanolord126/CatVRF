<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class ProductFlower
{
    public function __construct(
        public int $id,
        public int $productId,
        public int $flowerId,
        public string $flowerName,
        public int $quantity,
        public string $unit,
        public ?array $notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $productId,
        int $flowerId,
        string $flowerName,
        int $quantity,
        string $unit = 'stem',
        ?array $notes = null,
    ): self {
        return new self(
            id: 0,
            productId: $productId,
            flowerId: $flowerId,
            flowerName: $flowerName,
            quantity: $quantity,
            unit: $unit,
            notes: $notes,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }
}
