<?php

declare(strict_types=1);

namespace App\Domains\Furniture\DTOs;

final readonly class AIInteriorResultDto
{
    public function __construct(
        public array $recommendedProductIds,
        public int $estimatedTotal,
        public string $layoutStrategy,
        public array $styleAnalysis,
        public string $correlationId,
    ) {}

    public function toArray(): array
    {
        return [
            'recommended_product_ids' => $this->recommendedProductIds,
            'estimated_total' => $this->estimatedTotal,
            'layout_strategy' => $this->layoutStrategy,
            'style_analysis' => $this->styleAnalysis,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            recommendedProductIds: $data['recommended_product_ids'],
            estimatedTotal: $data['estimated_total'],
            layoutStrategy: $data['layout_strategy'],
            styleAnalysis: $data['style_analysis'],
            correlationId: $data['correlation_id'],
        );
    }
}
