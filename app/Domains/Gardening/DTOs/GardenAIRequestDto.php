<?php

declare(strict_types=1);

namespace App\Domains\Gardening\DTOs;

/**
     * AI Recommendation Request DTO
     * Used for building personal garden plans and climate-based advice.
     */
final readonly class GardenAIRequestDto
{
        public function __construct(
            public int $userId,
            public string $climateZone, // "Hardiness Zone 5b", etc.
            public string $plotType, // "Balcony", "Small Backyard", "Large Greenhouse"
            public array $interests, // ["Vegetables", "Flowers", "Sustainability"]
            private ?string $photoBase64 = null, // Optional for Computer Vision
            private string $correlationId = ""
        ) {}
    }
