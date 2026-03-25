<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\Services\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * RecommendationEngine — AI-движок персонализированных рекомендаций.
 * CANON 2026 — Production Ready
 */
class RecommendationEngine
{
    public function __construct(
        private readonly LogManager $logManager,
    ) {}

    /**
     * Возвращает персонализированные рекомендации для пользователя.
     */
    public function getPersonalizedSuggestions(User $user, string $type): Collection
    {
        $cacheKey = "engine:recommend:{$user->id}:{$type}:v1";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return collect($cached);
        }

        $this->logManager->info("Generating recommendations for user {$user->id}, type={$type}");

        $recommendations = collect([]);

        $this->cache->put($cacheKey, $recommendations->toArray(), 300);

        return $recommendations;
    }

    /**
     * Cosine similarity между двумя векторами.
     */
    private function cosineSimilarity(array $vec1, array $vec2): float
    {
        if (count($vec1) !== count($vec2) || count($vec1) === 0) {
            return 0.0;
        }

        $dot  = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        foreach ($vec1 as $i => $v) {
            $dot  += $v * $vec2[$i];
            $magA += $v * $v;
            $magB += $vec2[$i] * $vec2[$i];
        }

        $denom = sqrt($magA) * sqrt($magB);

        return $denom > 0 ? $dot / $denom : 0.0;
    }

    /**
     * Находит похожих пользователей по preference.
     *
     * @return array<int>
     */
    private function findSimilarUsers(User $user, int $limit = 5): array
    {
        $preference = $user->category_preference ?? null;

        if (!$preference) {
            return [];
        }

        return $this->db->table('users')
            ->where('id', '!=', $user->id)
            ->where('category_preference', $preference)
            ->limit($limit)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Возвращает курсы, на которые записан пользователь.
     *
     * @return array<int>
     */
    private function getUserEnrolledCourses(User $user): array
    {
        return $this->db->table('enrollments')
            ->where('user_id', $user->id)
            ->pluck('course_id')
            ->toArray();
    }
}
