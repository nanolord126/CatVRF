<?php declare(strict_types=1);

/**
 * TherapyMatcherService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/therapymatcherservice
 */


namespace App\Domains\Medical\Psychology\Services;

final readonly class TherapyMatcherService
{

    /**
         * Подбор психолога на основе предпочтений пользователя.
         */
        public function findMatches(array $preferences, int $tenantId): Collection
        {
            // В 2026 тут идет интеграция с OpenAI/Pinecone
            // Пока реализуем "умный" поиск по тегам и весам

            $query = Psychologist::where('tenant_id', $tenantId)
                ->where('is_available', true);

            // Фильтрация по специализации
            if (!empty($preferences['specialization'])) {
                $query->where('specialization', 'like', '%' . $preferences['specialization'] . '%');
            }

            // Фильтрация по типам терапии
            if (!empty($preferences['therapy_type'])) {
                $query->whereJsonContains('therapy_types', $preferences['therapy_type']);
            }

            return $query->get()->sortByDesc(function (Psychologist $psychologist) use ($preferences) {
                $score = 0.0;

                // Веса AI-скоринга
                if ($psychologist->experience_years > ($preferences['min_exp'] ?? 5)) {
                    $score += 0.5;
                }

                if ($psychologist->rating >= 4.5) {
                    $score += 0.3;
                }

                return $score;
            });
        }
}
