<?php

declare(strict_types=1);

namespace App\Domains\Furniture\DTOs;

/**
     * Request DTO for AI Interior Constructor.
     */
final readonly class AIInteriorRequestDto
{
        public function __construct(
            public int $roomTypeId,
            public string $stylePreference, // 'modern', 'minimalist', 'loft', etc.
            public int $budgetKopecks,
            private ?string $photoPath = null,
            private array $existingFurnitureIds = [],
            private ?string $correlationId = null
        ) {}
    }
