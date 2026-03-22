<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ML-based Recommendation Engine
 * Система рекомендаций на основе поведения, истории и сходства
 * 
 * @package App\Services\Analytics
 * @category ML / Recommendations
 */
final class RecommendationMLService
{
    private const CACHE_TTL = 300; // 5 минут для динамичности
    private const MIN_SCORE_THRESHOLD = 0.3; // 30% минимальный скор
    private const MAX_RECOMMENDATIONS = 10;

    /**
     * Получает персональные рекомендации для пользователя
     * Использует комбинированный подход: поведение (45%) + гео (25%) + embeddings (20%) + правила (10%)
     * 
     * @param int $userId
     * @param string|null $vertical
     * @param array $context
     * @return Collection
     */
    public function getForUser(int $userId, ?string $vertical = null, array $context = []): Collection
    {
        $cacheKey = $this->getCacheKey($userId, $vertical, $context);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $vertical, $context) {
            try {
                // Получаем рекомендации из каждого источника
                $behaviorRecs = $this->getRecommendationsByBehavior($userId, $vertical);
                $geoRecs = $this->getRecommendationsByGeo($userId, $vertical);
                $embeddingRecs = $this->getRecommendationsByEmbeddings($userId, $vertical);
                $businessRules = $this->getRecommendationsByBusinessRules($userId, $vertical);

                // Объединяем с взвешиванием
                $combined = $this->combineRecommendations(
                    $behaviorRecs,
                    $geoRecs,
                    $embeddingRecs,
                    $businessRules,
                    0.45, 0.25, 0.20, 0.10
                );

                // Сортируем по скору и возвращаем топ-10
                return $combined
                    ->where('score', '>=', self::MIN_SCORE_THRESHOLD)
                    ->sortByDesc('score')
                    ->take(self::MAX_RECOMMENDATIONS)
                    ->values();

            } catch (\Throwable $e) {
                Log::channel('analytics_errors')->error('Failed to generate recommendations', [
                    'user_id' => $userId,
                    'vertical' => $vertical,
                    'error' => $e->getMessage()
                ]);
                return collect();
            }
        });
    }

    /**
     * Рекомендации на основе истории покупок (поведение)
     * Анализирует просмотры, покупки, добавления в корзину
     * 
     * @param int $userId
     * @param string|null $vertical
     * @return Collection {id, score, reason, viewed_count, purchase_count}
     */
    private function getRecommendationsByBehavior(int $userId, ?string $vertical = null): Collection
    {
        // Получаем категории, которые смотрел пользователь
        $viewedCategories = DB::table('user_views')
            ->where('user_id', $userId)
            ->distinct('product_category_id')
            ->pluck('product_category_id')
            ->toArray();

        $query = DB::table('products')
            ->whereIn('category_id', $viewedCategories ?? [])
            ->where('status', 'active');

        if ($vertical) {
            $query->where('vertical', $vertical);
        }

        // Подсчитываем популярность в этих категориях
        $recommendations = $query
            ->selectRaw('
                products.id,
                COUNT(user_views.id) as view_count,
                COUNT(DISTINCT orders.id) as purchase_count,
                AVG(products.rating) as avg_rating
            ')
            ->leftJoin('user_views', 'products.id', '=', 'user_views.product_id')
            ->leftJoin('orders', 'products.id', '=', 'orders.product_id')
            ->groupBy('products.id')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'source' => 'behavior',
                    'score' => $this->calculateBehaviorScore(
                        $row->view_count,
                        $row->purchase_count,
                        $row->avg_rating
                    ),
                    'reason' => 'Based on your browsing history',
                ];
            });

        return collect($recommendations);
    }

    /**
     * Рекомендации на основе географии
     * Показывает популярное в районе пользователя
     * 
     * @param int $userId
     * @param string|null $vertical
     * @return Collection
     */
    private function getRecommendationsByGeo(int $userId, ?string $vertical = null): Collection
    {
        // Получаем локацию пользователя
        $userLocation = DB::table('users')
            ->where('id', $userId)
            ->select('latitude', 'longitude', 'city')
            ->first();

        if (!$userLocation) {
            return collect();
        }

        $radiusKm = 5; // Поиск в радиусе 5 км

        $query = DB::table('products')
            ->where('status', 'active')
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) < ?
            ", [$userLocation->latitude, $userLocation->longitude, $userLocation->latitude, $radiusKm])
            ->select('id', DB::raw('AVG(rating) as rating'), DB::raw('COUNT(*) as popularity'));

        if ($vertical) {
            $query->where('vertical', $vertical);
        }

        return $query
            ->groupBy('id')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'source' => 'geo',
                    'score' => min(1.0, ($row->popularity / 100) * ($row->rating / 5)),
                    'reason' => 'Popular in your area',
                ];
            });
    }

    /**
     * Рекомендации на основе embeddings (векторное сходство)
     * Использует cosine similarity между пользователем и товарами
     * 
     * @param int $userId
     * @param string|null $vertical
     * @return Collection
     */
    private function getRecommendationsByEmbeddings(int $userId, ?string $vertical = null): Collection
    {
        // Этот метод требует наличия embedding модели (например, OpenAI)
        
        $userEmbedding = Cache::get("user_embedding:{$userId}");
        if (!$userEmbedding) {
            return collect();
        }

        $query = DB::table('product_embeddings')
            ->select('product_id')
            ->whereRaw('cosine_similarity(?, embeddings) > 0.75', [$userEmbedding]);

        if ($vertical) {
            $query->join('products', 'product_embeddings.product_id', '=', 'products.id')
                ->where('products.vertical', $vertical);
        }

        return $query
            ->limit(self::MAX_RECOMMENDATIONS)
            ->get()
            ->map(function ($row) use ($userEmbedding) {
                // Используем cosine_similarity из zaprosа как скор
                return [
                    'id' => $row->product_id,
                    'source' => 'embedding',
                    'score' => (float) ($row->similarity ?? 0.75),
                    'reason' => 'Similar to items you like',
                ];
            });
    }

    /**
     * Рекомендации на основе бизнес-правил
     * Boosts/Demotes товары в зависимости от кампаний и стратегии
     * 
     * @param int $userId
     * @param string|null $vertical
     * @return Collection
     */
    private function getRecommendationsByBusinessRules(int $userId, ?string $vertical = null): Collection
    {
        $query = DB::table('products')
            ->where('status', 'active')
            ->select('id', 'boost_score', 'is_promoted');

        if ($vertical) {
            $query->where('vertical', $vertical);
        }

        return $query
            ->get()
            ->map(function ($row) {
                $score = $row->boost_score ?? 0.0;
                if ($row->is_promoted) {
                    $score += 0.15; // Boost для промо
                }
                
                return [
                    'id' => $row->id,
                    'source' => 'business_rules',
                    'score' => min(1.0, $score),
                    'reason' => 'Featured offer',
                ];
            });
    }

    /**
     * Объединяет рекомендации из разных источников с весами
     * 
     * @param Collection $behavior
     * @param Collection $geo
     * @param Collection $embedding
     * @param Collection $rules
     * @param float $behaviorWeight
     * @param float $geoWeight
     * @param float $embeddingWeight
     * @param float $rulesWeight
     * @return Collection
     */
    private function combineRecommendations(
        Collection $behavior,
        Collection $geo,
        Collection $embedding,
        Collection $rules,
        float $behaviorWeight,
        float $geoWeight,
        float $embeddingWeight,
        float $rulesWeight
    ): Collection {
        $allRecommendations = [];

        // Собираем все уникальные ID
        $allIds = collect([
            ...$behavior->pluck('id'),
            ...$geo->pluck('id'),
            ...$embedding->pluck('id'),
            ...$rules->pluck('id'),
        ])->unique();

        // Вычисляем взвешенный скор для каждого
        foreach ($allIds as $id) {
            $scores = [];
            $scores[] = ($behavior->firstWhere('id', $id)?->score ?? 0) * $behaviorWeight;
            $scores[] = ($geo->firstWhere('id', $id)?->score ?? 0) * $geoWeight;
            $scores[] = ($embedding->firstWhere('id', $id)?->score ?? 0) * $embeddingWeight;
            $scores[] = ($rules->firstWhere('id', $id)?->score ?? 0) * $rulesWeight;

            $combinedScore = array_sum($scores);

            if ($combinedScore > 0) {
                $allRecommendations[] = [
                    'id' => $id,
                    'score' => $combinedScore,
                ];
            }
        }

        return collect($allRecommendations);
    }

    /**
     * Вычисляет скор на основе поведения (просмотры, покупки, рейтинг)
     * 
     * @param int $viewCount
     * @param int $purchaseCount
     * @param float $avgRating
     * @return float
     */
    private function calculateBehaviorScore(int $viewCount, int $purchaseCount, float $avgRating): float
    {
        // Просмотры: логарифмическая шкала (10 просмотров = 0.5)
        $viewScore = min(1.0, log($viewCount + 1) / log(100));

        // Покупки: более важны (1 покупка = 0.4, 5 покупок = 1.0)
        $purchaseScore = min(1.0, $purchaseCount / 5);

        // Рейтинг
        $ratingScore = $avgRating / 5;

        // Взвешиваем: просмотры (30%), покупки (50%), рейтинг (20%)
        return ($viewScore * 0.3) + ($purchaseScore * 0.5) + ($ratingScore * 0.2);
    }

    /**
     * Генерирует ключ для кэша
     * 
     * @param int $userId
     * @param string|null $vertical
     * @param array $context
     * @return string
     */
    private function getCacheKey(int $userId, ?string $vertical = null, array $context = []): string
    {
        $geoHash = $context['geo_hash'] ?? 'default';
        $suffix = $vertical ? ":{$vertical}" : '';
        return "recommendations:user:{$userId}:geo:{$geoHash}{$suffix}:v1";
    }
}
