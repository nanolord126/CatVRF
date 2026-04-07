<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

/**
     * AISuggestionRequestDto - For AI constructor inputs.
     */
final readonly class AISuggestionRequestDto
{
        /**
         * @param array<string> $preferredBrands
         * @param array<string> $interests
         */
        public function __construct(
            public string $categorySlug,
            public int $budgetMaxKopecks,
            private array $preferredBrands = [],
            private array $interests = [],
            private string $correlationId = '') {}
}
