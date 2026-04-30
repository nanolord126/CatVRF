<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\DTOs;

final readonly class AIJewelryResultDto
{
    public function __construct(
        public array $recommendedProductIds,
        public array $suggestedMetals,
        public array $suggestedStones,
        public string $aiAdviceBrief,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'recommended_product_ids' => $this->recommendedProductIds,
            'suggested_metals' => $this->suggestedMetals,
            'suggested_stones' => $this->suggestedStones,
            'ai_advice_brief' => $this->aiAdviceBrief,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            recommendedProductIds: $data['recommended_product_ids'],
            suggestedMetals: $data['suggested_metals'],
            suggestedStones: $data['suggested_stones'],
            aiAdviceBrief: $data['ai_advice_brief'],
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
