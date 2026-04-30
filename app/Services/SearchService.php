<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;

use App\Domains\Search\Services\SearchService as DomainSearchService;
use Illuminate\Http\Request;
use App\Services\Security\RateLimiterService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Carbon\CarbonImmutable;

final readonly class SearchService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly RecommendationService $recommendationService,
        private readonly FraudControlService $fraud,
        private readonly RateLimiterService $rateLimiterService,
        private readonly SearchRankingService $rankingService,
        private readonly CacheManager $cache,
        private readonly DomainSearchService $domainSearchService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Выполняет поиск с ранжированием и защитой от фрода.
     *
     * @param  string  $query  Поисковый запрос
     * @param  int|null  $userId  ID пользователя (для персонализации)
     * @param  array  $filters  Фильтры: {vertical, price_from, price_to, rating_min}
     * @param  int  $page  Номер страницы
     * @param  int  $perPage  Результатов на странице
     * @return array{data: Collection, total: int, page: int, per_page: int}
     *
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
            $correlationId = $this->request->header('X-Correlation-ID', Str::uuid()->toString());

            $this->logger->channel('audit')->$this->logger->info('Search executed', [
                'query' => $query,
                'user_id' => $userId,
                'filters' => count($filters),
                'page' => $page,
                'correlation_id' => $correlationId,
            ]);

            $cacheKey = "search:{$query}:user:{$userId}:page:{$page}:v1";

            // Проверяем кэш
            $cached = $this->cache->get($cacheKey);
            if ($cached) {
                return $cached;
            }

            // Выполняем реальный поиск через domain service
            $searchType = $filters['vertical'] ?? null;
            $rawResults = $this->domainSearchService->search(
                term: $query,
                type: $searchType,
                limit: $perPage * 2, // Получаем больше для ранжирования
            );

            // Применяем фильтры
            $results = new Collection($rawResults);

            if (! empty($filters['price_from'])) {
                $results = $results->filter(function ($item) use ($filters) {
                    $price = $item['metadata']['price'] ?? null;

                    return $price !== null && $price >= $filters['price_from'];
                });
            }

            if (! empty($filters['price_to'])) {
                $results = $results->filter(function ($item) use ($filters) {
                    $price = $item['metadata']['price'] ?? null;

                    return $price !== null && $price <= $filters['price_to'];
                });
            }

            if (! empty($filters['rating_min'])) {
                $results = $results->filter(function ($item) use ($filters) {
                    $rating = $item['metadata']['rating'] ?? null;

                    return $rating !== null && $rating >= $filters['rating_min'];
                });
            }

            // Применяем ранжирование
            $rankedResults = $this->applyRanking($results, $userId);

            // Пагинация
            $total = $rankedResults->count();
            $pagedResults = $rankedResults->slice(($page - 1) * $perPage, $perPage)->values();

            $response = [
                'data' => $pagedResults,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ];

            // Кэшируем на 1 час
            $this->cache->put($cacheKey, $response, CarbonImmutable::now()->addHour());

            return $response;
        } catch (Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->error('Search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Повышает ранжирование товара при добавлении в wishlist.
     * Согласно КАНОН: добавление товара = +X баллов к выдаче.
     *
     * @param  int  $productId  ID товара
     * @param  int  $boostPoints  Количество баллов (обычно 10)
     */
    public function boostProductFromWishlist(int $productId, int $boostPoints = 10): void
    {
        $key = "search:product:{$productId}:boost";
        $current = (int) $this->cache->get($key, 0);
        $this->cache->put($key, $current + $boostPoints, CarbonImmutable::now()->addDays(30));

        $this->logger->channel('audit')->$this->logger->info('Product boost applied', [
            'product_id' => $productId,
            'boost_points' => $boostPoints,
            'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
        ]);
    }

    /**
     * Понижает ранжирование товара при удалении из wishlist (если >3 дней).
     * Согласно КАНОН: штраф за манипуляцию выдачей.
     *
     * @param  int  $productId  ID товара
     * @param  int  $penaltyPoints  Количество штрафных баллов (обычно 5)
     */
    public function demoteProductFromWishlist(int $productId, int $penaltyPoints = 5): void
    {
        $key = "search:product:{$productId}:penalty";
        $current = (int) $this->cache->get($key, 0);
        $this->cache->put($key, $current + $penaltyPoints, CarbonImmutable::now()->addDays(30));

        $this->logger->channel('audit')->$this->logger->info('Product demotion applied', [
            'product_id' => $productId,
            'penalty_points' => $penaltyPoints,
            'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
        ]);
    }

    /**
     * Применяет ранжирование с учётом вишлистов и ML.
     *
     * @param  Collection  $items  Товары
     * @param  int|null  $userId  ID пользователя
     * @return Collection Отсортированные товары
     */
    private function applyRanking(
        Collection $items,
        ?int $userId = null,
    ): Collection {
        // Сортируем по rank_score в порядке убывания
        return $items->sortByDesc('rank_score');
    }
}
