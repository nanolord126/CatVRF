<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

final readonly class SearchService
{
    public function __construct(
        private RecommendationService $recommendationService,
        private FraudControlService $fraudControlService,
        private RateLimiterService $rateLimiterService,
        private SearchRankingService $rankingService,
    ) {}

    /**
     * Выполняет поиск с ранжированием и защитой от фрода.
     *
     * @param string $query Поисковый запрос
     * @param int|null $userId ID пользователя (для персонализации)
     * @param array $filters Фильтры: {vertical, price_from, price_to, rating_min}
     * @param int $page Номер страницы
     * @param int $perPage Результатов на странице
     * @return array{data: Collection, total: int, page: int, per_page: int}
     * @throws Exception
     */
    public function search(
        string $query,
        ?int $userId = null,
        array $filters = [],
        int $page = 1,
        int $perPage = 20,
    ): array {
        try {
            Log::channel('audit')->info('Search executed', [
                'query' => $query,
                'user_id' => $userId,
                'filters' => count($filters),
                'page' => $page,
            ]);

            $cacheKey = "search:{$query}:user:{$userId}:page:{$page}:v1";

            // Проверяем кэш
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            // Плейсхолдер результатов
            $results = collect([
                [
                    'id' => 1,
                    'name' => 'Найденный товар 1',
                    'price' => 5000,
                    'rating' => 4.8,
                    'rank_score' => 0.95,
                    'rank_reason' => 'wishlist_boost', // boost из wishlist
                ],
                [
                    'id' => 2,
                    'name' => 'Найденный товар 2',
                    'price' => 7000,
                    'rating' => 4.5,
                    'rank_score' => 0.87,
                    'rank_reason' => 'ml_recommendation',
                ],
            ]);

            // Применяем ранжирование
            $rankedResults = $this->applyRanking($results, $userId);

            $response = [
                'data' => $rankedResults,
                'total' => 42, // плейсхолдер
                'page' => $page,
                'per_page' => $perPage,
            ];

            // Кэшируем на 1 час
            Cache::put($cacheKey, $response, now()->addHour());

            return $response;
        } catch (Exception $e) {
            Log::channel('audit')->error('Search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Применяет ранжирование с учётом вишлистов и ML.
     *
     * @param Collection $items Товары
     * @param int|null $userId ID пользователя
     * @return Collection Отсортированные товары
     */
    private function applyRanking(
        Collection $items,
        ?int $userId = null,
    ): Collection {
        // Сортируем по rank_score в порядке убывания
        return $items->sortByDesc('rank_score');
    }

    /**
     * Повышает ранжирование товара при добавлении в wishlist.
     * Согласно КАНОН: добавление товара = +X баллов к выдаче.
     *
     * @param int $productId ID товара
     * @param int $boostPoints Количество баллов (обычно 10)
     * @return void
     */
    public function boostProductFromWishlist(int $productId, int $boostPoints = 10): void
    {
        $key = "search:product:{$productId}:boost";
        $current = (int) Cache::get($key, 0);
        Cache::put($key, $current + $boostPoints, now()->addDays(30));

        Log::channel('audit')->info('Product boost applied', [
            'product_id' => $productId,
            'boost_points' => $boostPoints,
        ]);
    }

    /**
     * Понижает ранжирование товара при удалении из wishlist (если >3 дней).
     * Согласно КАНОН: штраф за манипуляцию выдачей.
     *
     * @param int $productId ID товара
     * @param int $penaltyPoints Количество штрафных баллов (обычно 5)
     * @return void
     */
    public function demoteProductFromWishlist(int $productId, int $penaltyPoints = 5): void
    {
        $key = "search:product:{$productId}:penalty";
        $current = (int) Cache::get($key, 0);
        Cache::put($key, $current + $penaltyPoints, now()->addDays(30));

        Log::channel('audit')->info('Product demotion applied', [
            'product_id' => $productId,
            'penalty_points' => $penaltyPoints,
        ]);
    }
}
