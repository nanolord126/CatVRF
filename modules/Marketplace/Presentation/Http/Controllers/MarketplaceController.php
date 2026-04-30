<?php

declare(strict_types=1);

namespace Modules\Marketplace\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Application\DTOs\SearchListingsDTO;
use Modules\Marketplace\Application\Services\MarketplaceAggregatorService;
use Modules\Marketplace\Application\Services\RankingEngineService;
use Modules\Marketplace\Application\Services\RecommendationService;
use Modules\Marketplace\Domain\Interfaces\ListingRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Контроллер маркетплейса для публичного API
 * Основной контроллер для витрины маркетплейса
 */
final class MarketplaceController
{
    public function __construct(
        private readonly ListingRepositoryInterface $listingRepository,
        private readonly RecommendationService $recommendationService,
        private readonly RankingEngineService $rankingEngine,
        private readonly MarketplaceAggregatorService $aggregator,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Получить главную витрину маркетплейса
     * Стартовая страница платформы
     */
    public function index(Request $request): JsonResponse
    {
        $this->logger->info('Marketplace index request', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $userId = $request->user()?->id;
            $limit = (int) $request->get('limit', 20);

            // Получаем смешанные рекомендации
            $recommendations = $userId !== null
                ? $this->recommendationService->getMixedRecommendations($userId, $limit)
                : $this->recommendationService->getColdStartRecommendations($limit);

            // Получаем featured позиции
            $featured = $this->listingRepository->findFeatured(10);

            // Получаем трендовые позиции
            $trending = $this->recommendationService->getTrendingListings(10);

            // Статистика ранжирования
            $rankingStats = $this->rankingEngine->getRankingStats();

            return response()->json([
                'data' => [
                    'recommendations' => array_map(
                        fn($l) => $l->toArray(),
                        $recommendations
                    ),
                    'featured' => array_map(
                        fn($l) => $l->toArray(),
                        $featured
                    ),
                    'trending' => array_map(
                        fn($l) => $l->toArray(),
                        $trending
                    ),
                ],
                'meta' => [
                    'total_listings' => $rankingStats['total_listings'],
                    'algorithm_version' => $rankingStats['algorithm_version'],
                    'enable_ml' => $rankingStats['enable_ml'],
                    'enable_personalization' => $rankingStats['enable_personalization'],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketplace index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to load marketplace',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Поиск позиций на маркетплейсе
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $dto = SearchListingsDTO::fromArray($request->all());
            $dto->validate();

            $result = $this->listingRepository->searchPaginated(
                $dto->toArray(),
                $dto->page,
                $dto->perPage,
            );

            return response()->json([
                'data' => array_map(
                    fn($l) => $l->toArray(),
                    $result['listings']
                ),
                'meta' => [
                    'total' => $result['total'],
                    'page' => $dto->page,
                    'per_page' => $dto->perPage,
                    'has_more' => $result['total'] > $dto->page * $dto->perPage,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Invalid search parameters',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Marketplace search failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Search failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить позицию по UUID
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $listing = $this->listingRepository->findByUuid(
                \Ramsey\Uuid\Uuid::fromString($uuid)
            );

            if ($listing === null) {
                return response()->json([
                    'error' => 'Listing not found',
                ], 404);
            }

            // Получаем похожие позиции
            $similar = $this->recommendationService->getSimilarListings(
                $listing->uuid,
                10
            );

            return response()->json([
                'data' => $listing->toArray(),
                'included' => [
                    'similar' => array_map(
                        fn($l) => $l->toArray(),
                        $similar
                    ),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketplace show failed', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to load listing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить рекомендации для пользователя
     */
    public function recommendations(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id;
            if ($userId === null) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 401);
            }

            $limit = (int) $request->get('limit', 20);
            $categories = $request->get('categories');
            $categoryArray = $categories !== null ? explode(',', $categories) : null;

            $recommendations = $this->recommendationService->getPersonalizedRecommendations(
                $userId,
                $limit,
                $categoryArray,
            );

            return response()->json([
                'data' => array_map(
                    fn($l) => $l->toArray(),
                    $recommendations
                ),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Recommendations failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to load recommendations',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить категории маркетплейса
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            // TODO: Implement category listing
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to load categories',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить позиции по категории
     */
    public function category(string $category, Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 20);
            $recommendations = $this->recommendationService->getCategoryRecommendations(
                $category,
                $limit,
            );

            return response()->json([
                'data' => array_map(
                    fn($l) => $l->toArray(),
                    $recommendations
                ),
                'meta' => [
                    'category' => $category,
                    'total' => count($recommendations),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Category listings failed', [
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to load category listings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить позиции по вертикали
     */
    public function vertical(string $vertical, Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 20);
            $recommendations = $this->recommendationService->getVerticalRecommendations(
                $vertical,
                $limit,
            );

            return response()->json([
                'data' => array_map(
                    fn($l) => $l->toArray(),
                    $recommendations
                ),
                'meta' => [
                    'vertical' => $vertical,
                    'total' => count($recommendations),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Vertical listings failed', [
                'vertical' => $vertical,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to load vertical listings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить трендовые позиции
     */
    public function trending(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 20);
            $trending = $this->recommendationService->getTrendingListings($limit);

            return response()->json([
                'data' => array_map(
                    fn($l) => $l->toArray(),
                    $trending
                ),
                'meta' => [
                    'total' => count($trending),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Trending listings failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to load trending listings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить featured позиции
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 10);
            $featured = $this->listingRepository->findFeatured($limit);

            return response()->json([
                'data' => array_map(
                    fn($l) => $l->toArray(),
                    $featured
                ),
                'meta' => [
                    'total' => count($featured),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Featured listings failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to load featured listings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статистику маркетплейса (admin endpoint)
     */
    public function stats(Request $request): JsonResponse
    {
        // TODO: Add authorization check
        try {
            $rankingStats = $this->rankingEngine->getRankingStats();
            $aggregationStats = $this->aggregator->getAggregationStats();

            return response()->json([
                'data' => [
                    'ranking' => $rankingStats,
                    'aggregation' => $aggregationStats,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketplace stats failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to load stats',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
