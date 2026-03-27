<?php

declare(strict_types=1);

namespace App\Domains\Luxury\DTO;

/**
 * LuxuryAIAnalysisRequestDTO
 *
 * Layer 5: Data Transfer Object
 * Описывает запрос на AI-анализ для Luxury конструктора.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final readonly class LuxuryAIAnalysisRequestDTO
{
    public function __construct(
        public string $clientUuid,
        public string $analysisType, // 'style_match', 'gift_curation', 'investment_watch'
        public ?string $promptText = null,
        public ?array $contextData = null,
        public string $correlationId
    ) {}
}
