<?php declare(strict_types=1);

namespace App\Services;


use Illuminate\Http\Request;
use App\Services\Security\RateLimiterService;
use Exception;
use Illuminate\Support\Collection;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

final readonly class SearchService
{

    public function __construct(
        private readonly Request $request,
            private RecommendationService $recommendationService,
            private FraudControlService $fraud,
            private RateLimiterService $rateLimiterService,
            private SearchRankingService $rankingService,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
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
                $this->logger->channel('audit')->info('Search executed', [
                    'query' => $query,
                    'user_id' => $userId,
                    'filters' => count($filters),
                    'page' => $page,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                $cacheKey = "search:{$query}:user:{$userId}:page:{$page}:v1";

                // Проверяем кэш
                $cached = $this->cache->get($cacheKey);
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
                $this->cache->put($cacheKey, $response, now()->addHour());

                return $response;
            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Search failed', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
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
            $current = (int) $this->cache->get($key, 0);
            $this->cache->put($key, $current + $boostPoints, now()->addDays(30));

            $this->logger->channel('audit')->info('Product boost applied', [
                'product_id' => $productId,
                'boost_points' => $boostPoints,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
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
            $current = (int) $this->cache->get($key, 0);
            $this->cache->put($key, $current + $penaltyPoints, now()->addDays(30));

            $this->logger->channel('audit')->info('Product demotion applied', [
                'product_id' => $productId,
                'penalty_points' => $penaltyPoints,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        }
}
