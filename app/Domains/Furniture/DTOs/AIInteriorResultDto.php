<?php

declare(strict_types=1);

namespace App\Domains\Furniture\DTOs;

/**
     * Result DTO from AI Interior Constructor.
     */
final readonly class AIInteriorResultDto
{
        public function __construct(
            public array $recommendedProductIds,
            public int $estimatedTotal,
            public string $layoutStrategy,
            public array $styleAnalysis,
            public string $correlationId
        ) {}
}
