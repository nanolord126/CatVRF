<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\Services;

use Modules\Marketplace\Application\DTOs\SearchListingsDTO;
use Modules\Marketplace\Domain\Entities\ProductListing;
use Modules\Marketplace\Domain\Interfaces\ListingRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Сервис рекомендаций для маркетплейса
 * Реализует различные алгоритмы рекомендаций
 */
final class RecommendationService
{
    private const DEFAULT_RECOMMENDATION_LIMIT = 20;
    private const SIMILARITY_THRESHOLD = 0.3;

    public function __construct(
        private readonly ListingRepositoryInterface $listingRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Получить персонализированные рекомендации для пользователя
     */
    public function getPersonalizedRecommendations(
        int $userId,
        ?int $limit = null,
        ?array $categories = null,
    ): array {
        $limit = $limit ?? self::DEFAULT_RECOMMENDATION_LIMIT;

        $this->logger->info('Generating personalized recommendations', [
            'user_id' => $userId,
            'limit' => $limit,
            'categories' => $categories,
        ]);

        // В проде здесь должна быть реальная персонализация на основе:
        // - Истории просмотров и заказов пользователя
        // - Collaborative filtering
        // - Content-based recommendations
        // - ML модели

        // Временная реализация: топ-ранжированные позиции
        $listings = $this->listingRepository->findTopRanked($limit * 2);

        // Фильтруем по категориям если указаны
        if ($categories !== null && !empty($categories)) {
            $listings = array_filter($listings, function ($listing) use ($categories) {
                return count(array_intersect($listing->categories, $categories)) > 0;
            });
        }

        // Оставляем только доступные
        $listings = array_filter($listings, fn($l) => $l->isAvailable());

        // Сортируем по ranking score
        usort($listings, fn($a, $b) => $b->rankingScore <=> $a->rankingScore);

        return array_slice($listings, 0, $limit);
    }

    /**
     * Получить рекомендации на основе позиции (похожие товары)
     */
    public function getSimilarListings(
        \Ramsey\Uuid\UuidInterface $listingUuid,
        ?int $limit = null,
    ): array {
        $limit = $limit ?? self::DEFAULT_RECOMMENDATION_LIMIT;

        $listing = $this->listingRepository->findByUuid($listingUuid);
        if ($listing === null) {
            throw new \InvalidArgumentException('Listing not found');
        }

        $this->logger->info('Finding similar listings', [
            'listing_uuid' => $listingUuid->toString(),
            'limit' => $limit,
        ]);

        // Ищем позиции по тем же категориям
        $candidates = [];
        foreach ($listing->categories as $category) {
            $categoryListings = $this->listingRepository->findByCategory($category);
            $candidates = array_merge($candidates, $categoryListings);
        }

        // Удаляем саму позицию и дубликаты
        $candidates = array_filter($candidates, fn($l) => $l->uuid->toString() !== $listingUuid->toString());
        $uniqueCandidates = [];
        $seen = [];
        foreach ($candidates as $candidate) {
            $uuid = $candidate->uuid->toString();
            if (!isset($seen[$uuid])) {
                $seen[$uuid] = true;
                $uniqueCandidates[] = $candidate;
            }
        }

        // Рассчитываем схожесть
        $similarListings = [];
        foreach ($uniqueCandidates as $candidate) {
            $similarity = $this->calculateSimilarity($listing, $candidate);
            if ($similarity >= self::SIMILARITY_THRESHOLD) {
                $similarListings[] = [
                    'listing' => $candidate,
                    'similarity' => $similarity,
                ];
            }
        }

        // Сортируем по схожести
        usort($similarListings, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        // Возвращаем только позиции
        return array_map(fn($item) => $item['listing'], array_slice($similarListings, 0, $limit));
    }

    /**
     * Получить рекомендации по категории
     */
    public function getCategoryRecommendations(
        string $category,
        ?int $limit = null,
    ): array {
        $limit = $limit ?? self::DEFAULT_RECOMMENDATION_LIMIT;

        $this->logger->info('Generating category recommendations', [
            'category' => $category,
            'limit' => $limit,
        ]);

        $listings = $this->listingRepository->findByCategory($category);

        // Фильтруем доступные
        $listings = array_filter($listings, fn($l) => $l->isAvailable());

        // Сортируем по ranking score
        usort($listings, fn($a, $b) => $b->rankingScore <=> $a->rankingScore);

        return array_slice($listings, 0, $limit);
    }

    /**
     * Получить трендовые позиции
     */
    public function getTrendingListings(?int $limit = null): array
    {
        $limit = $limit ?? self::DEFAULT_RECOMMENDATION_LIMIT;

        $this->logger->info('Generating trending recommendations', ['limit' => $limit]);

        // Получаем активные позиции
        $listings = $this->listingRepository->findActive();

        // Фильтруем доступные
        $listings = array_filter($listings, fn($l) => $l->isAvailable());

        // Сортируем по popularity score (временная метрика)
        usort($listings, fn($a, $b) => $b->popularityScore <=> $a->popularityScore);

        return array_slice($listings, 0, $limit);
    }

    /**
     * Получить рекомендации на основе поискового запроса
     */
    public function getSearchBasedRecommendations(
        string $query,
        ?int $limit = null,
    ): array {
        $limit = $limit ?? self::DEFAULT_RECOMMENDATION_LIMIT;

        $this->logger->info('Generating search-based recommendations', [
            'query' => $query,
            'limit' => $limit,
        ]);

        // Полно-textовый поиск
        $listings = $this->listingRepository->fullTextSearch($query);

        // Фильтруем доступные
        $listings = array_filter($listings, fn($l) => $l->isAvailable());

        return array_slice($listings, 0, $limit);
    }

    /**
     * Получить рекомендации для новой позиции (cold start)
     */
    public function getColdStartRecommendations(?int $limit = null): array
    {
        $limit = $limit ?? self::DEFAULT_RECOMMENDATION_LIMIT;

        $this->logger->info('Generating cold start recommendations', ['limit' => $limit]);

        // Для cold start используем featured и high-rated позиции
        $featured = $this->listingRepository->findFeatured($limit / 2);
        $topRanked = $this->listingRepository->findTopRanked($limit / 2);

        $recommendations = array_merge($featured, $topRanked);

        // Удаляем дубликаты
        $unique = [];
        $seen = [];
        foreach ($recommendations as $listing) {
            $uuid = $listing->uuid->toString();
            if (!isset($seen[$uuid])) {
                $seen[$uuid] = true;
                $unique[] = $listing;
            }
        }

        return array_slice($unique, 0, $limit);
    }

    /**
     * Рассчитать схожесть двух позиций
     * Использует контент-based подход
     */
    private function calculateSimilarity(ProductListing $a, ProductListing $b): float
    {
        $score = 0.0;
        $totalWeight = 0.0;

        // Схожесть по категориям (вес 0.4)
        $categoryWeight = 0.4;
        $categorySimilarity = $this->calculateCategorySimilarity($a, $b);
        $score += $categorySimilarity * $categoryWeight;
        $totalWeight += $categoryWeight;

        // Схожесть по тегам (вес 0.3)
        $tagWeight = 0.3;
        $tagSimilarity = $this->calculateTagSimilarity($a, $b);
        $score += $tagSimilarity * $tagWeight;
        $totalWeight += $tagWeight;

        // Схожесть по цене (вес 0.2)
        $priceWeight = 0.2;
        $priceSimilarity = $this->calculatePriceSimilarity($a, $b);
        $score += $priceSimilarity * $priceWeight;
        $totalWeight += $priceWeight;

        // Схожесть по типу (вес 0.1)
        $typeWeight = 0.1;
        $typeSimilarity = $a->type === $b->type ? 1.0 : 0.0;
        $score += $typeSimilarity * $typeWeight;
        $totalWeight += $typeWeight;

        return $totalWeight > 0 ? $score / $totalWeight : 0.0;
    }

    /**
     * Рассчитать схожесть по категориям (Jaccard index)
     */
    private function calculateCategorySimilarity(ProductListing $a, ProductListing $b): float
    {
        if (empty($a->categories) || empty($b->categories)) {
            return 0.0;
        }

        $intersection = count(array_intersect($a->categories, $b->categories));
        $union = count(array_unique(array_merge($a->categories, $b->categories)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Рассчитать схожесть по тегам (Jaccard index)
     */
    private function calculateTagSimilarity(ProductListing $a, ProductListing $b): float
    {
        if (empty($a->tags) || empty($b->tags)) {
            return 0.0;
        }

        $intersection = count(array_intersect($a->tags, $b->tags));
        $union = count(array_unique(array_merge($a->tags, $b->tags)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Рассчитать схожесть по цене
     */
    private function calculatePriceSimilarity(ProductListing $a, ProductListing $b): float
    {
        $priceA = $a->price->amount;
        $priceB = $b->price->amount;

        if ($priceA === 0.0 && $priceB === 0.0) {
            return 1.0;
        }

        $maxPrice = max($priceA, $priceB);
        if ($maxPrice === 0.0) {
            return 0.0;
        }

        $difference = abs($priceA - $priceB);
        return max(0, 1 - ($difference / $maxPrice));
    }

    /**
     * Получить рекомендации для конкретной вертикали
     */
    public function getVerticalRecommendations(
        string $vertical,
        ?int $limit = null,
    ): array {
        $limit = $limit ?? self::DEFAULT_RECOMMENDATION_LIMIT;

        $this->logger->info('Generating vertical recommendations', [
            'vertical' => $vertical,
            'limit' => $limit,
        ]);

        try {
            $source = \Modules\Marketplace\Domain\ValueObjects\VerticalSource::from($vertical);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException("Invalid vertical: {$vertical}");
        }

        $listings = $this->listingRepository->findByVertical($source);

        // Фильтруем доступные
        $listings = array_filter($listings, fn($l) => $l->isAvailable());

        // Сортируем по ranking score
        usort($listings, fn($a, $b) => $b->rankingScore <=> $a->rankingScore);

        return array_slice($listings, 0, $limit);
    }

    /**
     * Получить смешанные рекомендации (разные алгоритмы)
     */
    public function getMixedRecommendations(
        int $userId,
        ?int $limit = null,
    ): array {
        $limit = $limit ?? self::DEFAULT_RECOMMENDATION_LIMIT;

        $this->logger->info('Generating mixed recommendations', [
            'user_id' => $userId,
            'limit' => $limit,
        ]);

        // Получаем рекомендации из разных источников
        $personalized = $this->getPersonalizedRecommendations($userId, (int) ($limit * 0.4));
        $trending = $this->getTrendingListings((int) ($limit * 0.3));
        $featured = $this->listingRepository->findFeatured((int) ($limit * 0.3));

        // Объединяем и удаляем дубликаты
        $mixed = array_merge($personalized, $trending, $featured);
        $unique = [];
        $seen = [];
        foreach ($mixed as $listing) {
            $uuid = $listing->uuid->toString();
            if (!isset($seen[$uuid])) {
                $seen[$uuid] = true;
                $unique[] = $listing;
            }
        }

        return array_slice($unique, 0, $limit);
    }
}
