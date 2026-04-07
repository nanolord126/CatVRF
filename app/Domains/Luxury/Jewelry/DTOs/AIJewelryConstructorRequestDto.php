<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\DTOs;

/**
     * AIJewelryConstructorRequestDto (Layer 2/9)
     */
final readonly class AIJewelryConstructorRequestDto
{
        public function __construct(
            public string $stylePreference, // minimalist, vintage, luxury, art-deco
            public string $colorType, // warm-spring, cool-summer, warm-autumn, cool-winter
            public string $occasion, // wedding, party, everyday, corporate
            public int $budgetLimit, // Max Price in Kopecks
            private ?string $photoPath = null,
            private ?string $correlationId = null
        ) {}
    }
