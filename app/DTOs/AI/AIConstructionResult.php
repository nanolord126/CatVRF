<?php declare(strict_types=1);

/**
 * AIConstructionResult — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 * @see https://catvrf.ru/docs/aiconstructionresult
 */


namespace App\DTOs\AI;

final class AIConstructionResult
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
