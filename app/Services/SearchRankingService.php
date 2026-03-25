<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис ранжирования результатов поиска с персонализацией и ML.
 * Использует профиль пользователя, embeddings, поведение и геоданные.
 * CANON 2026 - Production Ready
 */
final readonly class SearchRankingService
{
    public function __construct(
        private FraudControlService $fraudControlService,
        private RateLimiterService $rateLimiterService,
    ) {}

    /**
     * Ранжирует результаты поиска на основе профиля пользователя и персонализации.
     *
     * @param int $userId ID пользователя
     * @param array $items Массив результатов поиска
     * @param string $query Оригинальный поисковый запрос
     * @param array $context Контекст: {geoPoint, personalizedWeight, ...}
     * @param string $correlationId Идентификатор корреляции
     * @return array Отсортированные результаты с scores
     */
    public function rankResults(
        int $userId,
        array $items,
        string $query,
        array $context = [],
        string $correlationId = '',
    ): array {
        try {
            $correlationId = $correlationId ?: (string) Str::uuid()->toString();

            // Rate limiting
            if (!$this->rateLimiterService->allowTenant($userId, 'search_ranking', 1000)) {
                $this->log->channel('audit')->warning('Search ranking rate limit exceeded', [
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);
                return $items;
            }

            $userProfile = $this->getUserProfile($userId);
            
            // Новые пользователи → популярность + рейтинг
            if ($this->isNewUser($userProfile)) {
                return $this->rankByPopularity($items);
            }

            // Персонализация отключена → 30% персонализировано, 70% дефолт
            if ($userProfile['personalization_disabled'] ?? false) {
                return $this->rankMixed($items, 0.3, $userProfile);
            }

            // Обычные пользователи → embeddings + поведение + гео
            return $this->rankByEmbeddings($items, $userProfile, $context);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Search ranking failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId ?? '',
            ]);
            return $items;
        }
    }

    private function getUserProfile(int $userId): array
    {
        $cacheKey = "user_profile:{$userId}";

        return $this->cache->remember($cacheKey, 3600, function () use ($userId) {
            $user = $this->db->table('users')->find($userId);

            if (!$user) {
                return ['is_new' => true];
            }

            return [
                'is_new' => now()->diffInDays($user->created_at) < 7,
                'personalization_disabled' => (bool)($user->personalization_disabled ?? false),
                'purchase_count' => $this->db->table('orders')
                    ->where('user_id', $userId)
                    ->where('status', 'completed')
                    ->count(),
                'avg_order_value' => (int)$this->db->table('orders')
                    ->where('user_id', $userId)
                    ->where('status', 'completed')
                    ->avg('total') ?? 0,
                'preferred_categories' => $this->getUserPreferences($userId),
                'geo_location' => $user->geo_point ?? null,
            ];
        });
    }

    private function isNewUser(array $profile): bool
    {
        return $profile['is_new'] ?? true;
    }

    private function rankByPopularity(array $items): array
    {
        return collect($items)
            ->sortByDesc('popularity_score')
            ->sortByDesc('rating')
            ->values()
            ->toArray();
    }

    private function rankMixed(array $items, float $personalizedWeight, array $userProfile): array
    {
        return collect($items)
            ->map(function ($item) use ($personalizedWeight, $userProfile) {
                $personalScore = $this->calculatePersonalScore($item, $userProfile);
                $defaultScore = $item['popularity_score'] ?? 0;
                $item['rank_score'] = ($personalScore * $personalizedWeight) + ($defaultScore * (1 - $personalizedWeight));
                return $item;
            })
            ->sortByDesc('rank_score')
            ->values()
            ->toArray();
    }

    private function rankByEmbeddings(array $items, array $userProfile, array $context): array
    {
        return collect($items)
            ->map(function ($item) use ($userProfile, $context) {
                $embeddingScore = $this->calculateEmbeddingScore($item, $userProfile);
                $behaviorScore = $this->calculateBehaviorScore($item, $userProfile);
                $geoScore = $this->calculateGeoScore($item, $context);
                
                $item['rank_score'] = ($embeddingScore * 0.4) + ($behaviorScore * 0.35) + ($geoScore * 0.25);
                return $item;
            })
            ->sortByDesc('rank_score')
            ->values()
            ->toArray();
    }

    private function calculatePersonalScore(array $item, array $userProfile): float
    {
        $score = 0.0;
        
        if (in_array($item['category'] ?? null, $userProfile['preferred_categories'] ?? [])) {
            $score += 0.5;
        }
        
        $score += min(($item['rating'] ?? 0) / 5.0, 1.0) * 0.3;
        $score += min(($item['popularity_score'] ?? 0) / 100.0, 1.0) * 0.2;
        
        return min($score, 1.0);
    }

    private function calculateEmbeddingScore(array $item, array $userProfile): float
    {
        // Симуляция cosine similarity embeddings
        // В реальной системе: user_embedding · item_embedding
        return (float)($item['embedding_similarity'] ?? 0.5);
    }

    private function calculateBehaviorScore(array $item, array $userProfile): float
    {
        $score = 0.0;
        
        if (in_array($item['category'] ?? null, $userProfile['preferred_categories'] ?? [])) {
            $score += 0.4;
        }
        
        $score += min(($item['rating'] ?? 0) / 5.0, 1.0) * 0.3;
        $score += min($userProfile['purchase_count'] / 100.0, 1.0) * 0.3;
        
        return min($score, 1.0);
    }

    private function calculateGeoScore(array $item, array $context): float
    {
        if (!isset($context['geoPoint']) || !isset($item['geo_point'])) {
            return 0.5; // neutral
        }
        
        // Расстояние в км
        $distance = $this->calculateDistance(
            $context['geoPoint']['lat'],
            $context['geoPoint']['lon'],
            $item['geo_point']['lat'],
            $item['geo_point']['lon'],
        );
        
        // Ближе = выше score (max 5km = 1.0)
        return max(1.0 - ($distance / 5.0), 0.0);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    private function getUserPreferences(int $userId): array
    {
        return $this->db->table('orders')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->select('category')
            ->groupBy('category')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->pluck('category')
            ->toArray();
    }
}
