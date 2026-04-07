<?php declare(strict_types=1);

/**
 * AIPropertyMatchResultDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/aipropertymatchresultdto
 */


namespace App\Domains\RealEstate\DTO;

/**
 * Class AIPropertyMatchResultDTO
 *
 * Part of the RealEstate vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final readonly class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\RealEstate\DTO
 */
final readonly class AIPropertyMatchResultDTO
{

    /**
         * КАНОН 2026: DTO для результатов AI сопоставления.
         *
         * @param Collection $matchedProperties Коллекция найденных объектов Property
         * @param array $scores Ассоциативный массив [property_uuid => match_score]
         * @param string $dream Исходный запрос пользователя
         * @param string $correlation_id Идентификатор трассировки
         */
        public function __construct(
            public Collection $matchedProperties,
            public array $scores,
            public string $dream,
            public string $correlation_id
        ) {}

        /**
         * Handle toArray operation.
         *
         * @throws \DomainException
         */
        public function toArray(): array
        {
            return [
                'count' => $this->matchedProperties->count(),
                'dream' => $this->dream,
                'correlation_id' => $this->correlation_id,
                'matches' => $this->matchedProperties->map(fn ($p) => [
                    'uuid' => $p->uuid,
                    'name' => $p->name,
                    'score' => $this->scores[$p->uuid] ?? 0,
                    'price' => $p->listings->first()?->price ?? 0,
                ])->toArray(),
            ];
        }
}
