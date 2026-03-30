<?php declare(strict_types=1);

namespace App\Data\DTO\AI\Constructors;

final readonly class RecommendedProductDTO
{
    public function __construct(
        public int $productId,
        public string $name,
        public float $matchScore,
        public string $reason,
        public int $price,
    ) {
    }
}
