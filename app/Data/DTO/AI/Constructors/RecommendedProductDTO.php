<?php

declare(strict_types=1);

namespace App\Data\DTO\AI\Constructors;

use Spatie\LaravelData\Data;

final class RecommendedProductDTO extends Data
{
    public function __construct(
        public readonly int $productId,
        public readonly string $name,
        public readonly float $matchScore,
        public readonly string $reason,
        public readonly int $price,
    ) {
    }
}
