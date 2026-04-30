<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\Services;

use Modules\Marketplace\Domain\Entities\ProductListing;
use Modules\Marketplace\Domain\Interfaces\ListingRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Str;

/**
 * Сервис показа рекламы на главной странице (homepage/titul)
 * 
 * Интегрирован с ProductListing счетчиками показов и кликов
 * Работает без фасадов, используя только Domain и Repository слои
 */
final readonly class HomepageAdService
{
    private const HOMEPAGE_HERO_LIMIT = 5;
    private const HOMEPAGE_BANNER_LIMIT = 10;
    private const HOMEPAGE_FEED_LIMIT = 20;
    
    private const PROMOTION_TYPES = [
        'homepage_hero',
        'homepage_banner',
        'feed_top',
        'category_top',
        'search_top',
    ];

    public function __construct(
        private readonly ListingRepositoryInterface $listingRepository,
        private readonly RankingEngineService $rankingEngine,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Получить рекламные позиции для главной страницы
     * 
     * @param array{
     *   vertical?: string,
     *   user_id?: int,
     *   limit?: int,
     *   promotion_type?: string,
     * } $filters
     * @return array<ProductListing>
     */
    public function getHomepageAds(array $filters = []): array
    {
        $vertical = $filters['vertical'] ?? null;
        $limit = $filters['limit'] ?? self::HOMEPAGE_FEED_LIMIT;
        $promotionType = $filters['promotion_type'] ?? null;
        $userId = $filters['user_id'] ?? null;

        $this->logger->info('Fetching homepage ads', [
            'vertical' => $vertical,
            'limit' => $limit,
            'promotion_type' => $promotionType,
            'user_id' => $userId,
        ]);

        // Получаем все активные позиции с рекламой
        $listings = $this->findPromotedListings($vertical, $promotionType);

        // Фильтруем только те, которые могут показываться на главной
        $homepageAds = array_filter($listings, fn (ProductListing $listing) => $listing->canShowOnHomepage());

        // Фильтруем по активной рекламе и бюджету
        $activeAds = array_filter($homepageAds, fn (ProductListing $listing) => 
            $listing->isPromotionActive() && !$listing->isPromotionBudgetExceeded()
        );

        // Сортируем по приоритету (учитывая promotion_priority_boost)
        usort($activeAds, fn (ProductListing $a, ProductListing $b) => 
            $b->getAdPosition() <=> $a->getAdPosition()
        );

        // Пересчитываем ранжирование для топ позиций
        $topAds = array_slice($activeAds, 0, $limit);
        foreach ($topAds as $ad) {
            $this->rankingEngine->calculateRanking($ad);
        }

        // Сортируем по ranking_score
        usort($topAds, fn (ProductListing $a, ProductListing $b) => 
            $b->rankingScore <=> $a->rankingScore
        );

        $this->logger->info('Homepage ads fetched', [
            'total' => count($activeAds),
            'returned' => count($topAds),
        ]);

        return $topAds;
    }

    /**
     * Получить hero-позиции для главной страницы (максимальная видимость)
     */
    public function getHeroPositions(?string $vertical = null): array
    {
        return $this->getHomepageAds([
            'vertical' => $vertical,
            'limit' => self::HOMEPAGE_HERO_LIMIT,
            'promotion_type' => 'homepage_hero',
        ]);
    }

    /**
     * Получить banner-позиции для главной страницы
     */
    public function getBannerPositions(?string $vertical = null): array
    {
        return $this->getHomepageAds([
            'vertical' => $vertical,
            'limit' => self::HOMEPAGE_BANNER_LIMIT,
            'promotion_type' => 'homepage_banner',
        ]);
    }

    /**
     * Получить feed-позиции для ленты на главной
     */
    public function getFeedPositions(?string $vertical = null, int $limit = self::HOMEPAGE_FEED_LIMIT): array
    {
        return $this->getHomepageAds([
            'vertical' => $vertical,
            'limit' => $limit,
            'promotion_type' => 'feed_top',
        ]);
    }

    /**
     * Записать показ рекламы на главной странице
     * Обновляет счетчики в ProductListing
     */
    public function recordHomepageImpression(
        UuidInterface $listingUuid, 
        int $costKopecks = 10,
        ?string $correlationId = null
    ): ProductListing {
        $correlationId ??= Str::uuid()->toString();
        
        $listing = $this->listingRepository->findByUuid($listingUuid);
        if ($listing === null) {
            throw new \InvalidArgumentException("Listing not found: {$listingUuid}");
        }

        if (!$listing->isPromotionActive()) {
            throw new \RuntimeException("Listing promotion is not active");
        }

        if ($listing->isPromotionBudgetExceeded()) {
            throw new \RuntimeException("Listing promotion budget exceeded");
        }

        // Записываем показ через entity
        $updatedListing = $listing->recordImpression($costKopecks);
        $this->listingRepository->save($updatedListing);

        $this->logger->info('Homepage ad impression recorded', [
            'listing_uuid' => $listingUuid->toString(),
            'cost_kopecks' => $costKopecks,
            'total_impressions' => $updatedListing->getPromotionImpressions(),
            'total_spend' => $updatedListing->getPromotionSpendKopecks(),
            'correlation_id' => $correlationId,
        ]);

        return $updatedListing;
    }

    /**
     * Записать клик по рекламе на главной странице
     * Обновляет счетчики в ProductListing
     */
    public function recordHomepageClick(
        UuidInterface $listingUuid, 
        int $costKopecks = 50,
        ?string $correlationId = null
    ): ProductListing {
        $correlationId ??= Str::uuid()->toString();
        
        $listing = $this->listingRepository->findByUuid($listingUuid);
        if ($listing === null) {
            throw new \InvalidArgumentException("Listing not found: {$listingUuid}");
        }

        if (!$listing->isPromotionActive()) {
            throw new \RuntimeException("Listing promotion is not active");
        }

        if ($listing->isPromotionBudgetExceeded()) {
            throw new \RuntimeException("Listing promotion budget exceeded");
        }

        // Записываем клик через entity
        $updatedListing = $listing->recordClick($costKopecks);
        $this->listingRepository->save($updatedListing);

        $this->logger->info('Homepage ad click recorded', [
            'listing_uuid' => $listingUuid->toString(),
            'cost_kopecks' => $costKopecks,
            'total_clicks' => $updatedListing->getPromotionClicks(),
            'total_spend' => $updatedListing->getPromotionSpendKopecks(),
            'ctr' => $updatedListing->getPromotionCTR(),
            'correlation_id' => $correlationId,
        ]);

        return $updatedListing;
    }

    /**
     * Получить статистику рекламных позиций на главной
     */
    public function getHomepageAdStats(?string $vertical = null): array
    {
        $listings = $this->findPromotedListings($vertical);
        
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalSpend = 0;
        $activeCampaigns = 0;
        $exceededBudget = 0;

        foreach ($listings as $listing) {
            $totalImpressions += $listing->getPromotionImpressions();
            $totalClicks += $listing->getPromotionClicks();
            $totalSpend += $listing->getPromotionSpendKopecks();
            
            if ($listing->isPromotionActive()) {
                $activeCampaigns++;
            }
            
            if ($listing->isPromotionBudgetExceeded()) {
                $exceededBudget++;
            }
        }

        $avgCTR = $totalImpressions > 0 ? $totalClicks / $totalImpressions : 0.0;

        return [
            'total_listings' => count($listings),
            'active_campaigns' => $activeCampaigns,
            'exceeded_budget' => $exceededBudget,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_spend_kopecks' => $totalSpend,
            'total_spend_rub' => $totalSpend / 100,
            'avg_ctr' => $avgCTR,
        ];
    }

    /**
     * Найти продвигаемые позиции
     */
    private function findPromotedListings(?string $vertical = null, ?string $promotionType = null): array
    {
        $query = $this->listingRepository->findActive();
        
        $filtered = array_filter($query, function (ProductListing $listing) use ($vertical, $promotionType) {
            if (!$listing->isPromoted) {
                return false;
            }

            if ($vertical !== null && $listing->source->value !== $vertical) {
                return false;
            }

            if ($promotionType !== null && $listing->promotionType !== $promotionType) {
                return false;
            }

            return true;
        });

        return array_values($filtered);
    }

    /**
     * Проверить валидность типа рекламы
     */
    public function isValidPromotionType(string $type): bool
    {
        return in_array($type, self::PROMOTION_TYPES, true);
    }

    /**
     * Получить доступные типы рекламы для главной страницы
     */
    public function getHomepagePromotionTypes(): array
    {
        return [
            'homepage_hero' => [
                'name' => 'Hero Position',
                'description' => 'Максимальная видимость на главной странице',
                'priority_boost' => 100,
                'cost_per_impression_kopecks' => 20,
            ],
            'homepage_banner' => [
                'name' => 'Banner Position',
                'description' => 'Баннер на главной странице',
                'priority_boost' => 75,
                'cost_per_impression_kopecks' => 15,
            ],
            'feed_top' => [
                'name' => 'Feed Top',
                'description' => 'Позиция в верхней части ленты',
                'priority_boost' => 50,
                'cost_per_impression_kopecks' => 10,
            ],
        ];
    }
}
