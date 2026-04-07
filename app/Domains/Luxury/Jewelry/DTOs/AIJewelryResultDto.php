<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\DTOs;

/**
     * AIJewelryResultDto (Layer 2/9)
     */
final readonly class AIJewelryResultDto
{
        public function __construct(
            public array $recommendedProductIds,
            public array $suggestedMetals,
            public array $suggestedStones,
            public string $aiAdviceBrief,
            private ?string $correlationId = null
        ) {}
}
