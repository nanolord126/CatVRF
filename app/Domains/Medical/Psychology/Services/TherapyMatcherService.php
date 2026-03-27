<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Services;

use App\Domains\Medical\Psychology\Models\Psychologist;
use Illuminate\Support\Collection;

/**
 * AI-слой для подбора специалиста.
 */
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
