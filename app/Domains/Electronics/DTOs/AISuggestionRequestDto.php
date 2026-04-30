<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class AISuggestionRequestDto
{
    public function __construct(
        public string $categorySlug,
        public int $budgetMaxKopecks,
        public array $preferredBrands = [],
        public array $interests = [],
        public string $correlationId = '',
    ) {}

    public function toArray(): array
    {
        return [
            'category_slug' => $this->categorySlug,
            'budget_max_kopecks' => $this->budgetMaxKopecks,
            'preferred_brands' => $this->preferredBrands,
            'interests' => $this->interests,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            categorySlug: $data['category_slug'],
            budgetMaxKopecks: $data['budget_max_kopecks'],
            preferredBrands: $data['preferred_brands'] ?? [],
            interests: $data['interests'] ?? [],
            correlationId: $data['correlation_id'] ?? '',
        );
    }
}
