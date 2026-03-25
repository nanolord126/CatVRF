<?php

declare(strict_types=1);

namespace App\DTOs\AI;

/**
 * DTO для результатов работы AI-конструктора
 * Соответствует КАНОНУ 2026
 */
final readonly class AIConstructionResult
{
    public function __construct(
        public string $vertical,
        public string $type, // 'image', 'list', 'design', 'calculation'
        public array $payload, // Основные данные генерации
        public array $suggestions, // Рекомендованные товары из Inventory
        public float $confidence_score,
        public string $correlation_id
    ) {}

    /**
     * Преобразовать в массив для ответа/логирования
     */
    public function toArray(): array
    {
        return [
            'vertical' => $this->vertical,
            'type' => $this->type,
            'payload' => $this->payload,
            'suggestions' => $this->suggestions,
            'confidence_score' => $this->confidence_score,
            'correlation_id' => $this->correlation_id,
        ];
    }
}
