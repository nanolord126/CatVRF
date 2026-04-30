<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\Services;

use Modules\Marketplace\Application\DTOs\RankingConfigDTO;
use Modules\Marketplace\Domain\Entities\ProductListing;
use Modules\Marketplace\Domain\Entities\RankingScore;
use Modules\Marketplace\Domain\Interfaces\ListingRepositoryInterface;
use Modules\Marketplace\Domain\Interfaces\RankingRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Сервис ранжирования витринных позиций
 * Реализует алгоритмы расчета рейтингов на основе множества факторов
 */
final class RankingEngineService
{
    private const DEFAULT_RECENT_DAYS = 30;
    private const DEFAULT_POPULARITY_WINDOW = 7;
    private const DEFAULT_DECAY_FACTOR = 0.95;

    public function __construct(
        private readonly ListingRepositoryInterface $listingRepository,
        private readonly RankingRepositoryInterface $rankingRepository,
        private readonly LoggerInterface $logger,
        private readonly RankingConfigDTO $config,
    ) {}

    /**
     * Рассчитать рейтинг для одной позиции
     */
    public function calculateRanking(ProductListing $listing): RankingScore
    {
        $popularityScore = $this->calculatePopularityScore($listing);
        $conversionScore = $this->calculateConversionScore($listing);
        $recencyScore = $this->calculateRecencyScore($listing);
        $ratingScore = $this->calculateRatingScore($listing);
        $priceScore = $this->calculatePriceScore($listing);
        $availabilityScore = $this->calculateAvailabilityScore($listing);
        $promotedScore = $this->calculatePromotedScore($listing);
        $promotionBoostScore = $this->calculatePromotionBoostScore($listing);

        // ML score - если включен, будет заменен на реальный ML-прогноз
        $mlScore = $this->config->enableML ? $this->calculateMLScore($listing) : 0.0;

        // Personalization score - если включен, будет заменен на персонализированный скор
        $personalizationScore = $this->config->enablePersonalization ? $this->calculatePersonalizationScore($listing) : 0.0;

        $factors = [
            'popularity' => $popularityScore,
            'conversion' => $conversionScore,
            'recency' => $recencyScore,
            'rating' => $ratingScore,
            'price' => $priceScore,
            'availability' => $availabilityScore,
            'promoted' => $promotedScore,
            'promotion_boost' => $promotionBoostScore,
            'ml' => $mlScore,
            'personalization' => $personalizationScore,
        ];

        $rankingScore = RankingScore::fromMetrics(
            listingUuid: $listing->uuid,
            popularityScore: $popularityScore,
            conversionScore: $conversionScore,
            recencyScore: $recencyScore,
            ratingScore: $ratingScore,
            priceScore: $priceScore,
            availabilityScore: $availabilityScore,
            promotedScore: $promotedScore,
            promotionBoostScore: $promotionBoostScore,
            mlScore: $mlScore,
            personalizationScore: $personalizationScore,
            factors: $factors,
            algorithmVersion: $this->config->algorithmVersion,
        );

        $this->rankingRepository->save($rankingScore);

        return $rankingScore;
    }

    /**
     * Массовый пересчет рейтингов
     */
    public function recalculateAll(?int $limit = null): array
    {
        $this->logger->info('Starting bulk ranking recalculation', ['limit' => $limit]);

        $listings = $this->listingRepository->findActive();
        if ($limit !== null) {
            $listings = array_slice($listings, 0, $limit);
        }

        $rankings = [];
        foreach ($listings as $listing) {
            try {
                $rankings[] = $this->calculateRanking($listing);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to calculate ranking for listing', [
                    'listing_uuid' => $listing->uuid->toString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Bulk ranking recalculation completed', ['count' => count($rankings)]);

        return $rankings;
    }

    /**
     * Пересчет просроченных рейтингов
     */
    public function recalculateExpired(): int
    {
        $expiredScores = $this->rankingRepository->findExpired();
        $count = 0;

        foreach ($expiredScores as $score) {
            $listing = $this->listingRepository->findByUuid($score->listingUuid);
            if ($listing !== null) {
                $this->calculateRanking($listing);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Расчет популярности на основе просмотров и заказов
     * Использует экспоненциальное затухание для недавних событий
     */
    private function calculatePopularityScore(ProductListing $listing): float
    {
        if ($listing->viewCount === 0 && $listing->orderCount === 0) {
            return 0.0;
        }

        // Базовый score на основе view count
        $viewScore = min($listing->viewCount / 1000, 1.0);

        // Бонус за заказы (вес заказов выше)
        $orderBonus = min($listing->orderCount / 100, 1.0) * 0.5;

        // Комбинированный score
        $baseScore = ($viewScore * 0.6) + ($orderBonus * 0.4);

        // Применяем decay factor в зависимости от возраста
        $daysSinceCreation = (new \DateTimeImmutable())->diff($listing->createdAt)->days;
        $decay = pow(self::DEFAULT_DECAY_FACTOR, $daysSinceCreation / self::DEFAULT_POPULARITY_WINDOW);

        return min($baseScore * $decay, 1.0);
    }

    /**
     * Расчет конверсии (заказы / просмотры)
     */
    private function calculateConversionScore(ProductListing $listing): float
    {
        if ($listing->viewCount === 0) {
            return 0.0;
        }

        $conversionRate = $listing->orderCount / $listing->viewCount;

        // Нормализуем: 5% конверсия = 1.0 score
        $normalizedScore = min($conversionRate / 0.05, 1.0);

        // Минимальный порог для новых позиций
        if ($listing->viewCount < 10) {
            $normalizedScore *= 0.5;
        }

        return $normalizedScore;
    }

    /**
     * Расчет свежести (recency score)
     * Новые позиции получают бонус
     */
    private function calculateRecencyScore(ProductListing $listing): float
    {
        $now = new \DateTimeImmutable();
        $daysSinceUpdate = $now->diff($listing->updatedAt)->days;

        // Линейное затухание: чем новее, тем выше score
        if ($daysSinceUpdate === 0) {
            return 1.0;
        }

        $score = max(0, 1 - ($daysSinceUpdate / self::DEFAULT_RECENT_DAYS));

        // Бонус для недавно опубликованных
        if ($listing->publishedAt !== null) {
            $daysSincePublish = $now->diff($listing->publishedAt)->days;
            if ($daysSincePublish < 7) {
                $score = min($score + 0.2, 1.0);
            }
        }

        return $score;
    }

    /**
     * Расчет рейтинга на основе отзывов
     */
    private function calculateRatingScore(ProductListing $listing): float
    {
        if ($listing->rating === null || $listing->reviewCount === 0) {
            return 0.3; // Базовый score для позиций без отзывов
        }

        // Нормализуем рейтинг 0-5 в 0-1
        $normalizedRating = $listing->rating->value / 5.0;

        // Вес на основе количества отзывов (больше отзывов = выше доверие)
        $reviewWeight = min($listing->reviewCount / 50, 1.0);

        // Комбинированный score
        $score = ($normalizedRating * 0.7) + ($reviewWeight * 0.3);

        // Бонус для отличных рейтингов
        if ($listing->rating->isExcellent()) {
            $score = min($score + 0.1, 1.0);
        }

        return $score;
    }

    /**
     * Расчет ценового score
     * Предпочтение среднему ценовому сегменту
     */
    private function calculatePriceScore(ProductListing $listing): float
    {
        // Это упрощенная версия. В проде нужно использовать перцентили по категории
        $price = $listing->price->amount;

        // Оптимальный диапазон (например, 1000-10000 RUB)
        $minOptimal = 1000.0;
        $maxOptimal = 10000.0;

        if ($price < $minOptimal) {
            // Слишком дешево - возможно низкое качество
            return min($price / $minOptimal, 0.7);
        }

        if ($price > $maxOptimal) {
            // Слишком дорого - ограниченный спрос
            return max(0.5, 1 - (($price - $maxOptimal) / $maxOptimal));
        }

        // В оптимальном диапазоне
        return 1.0;
    }

    /**
     * Расчет доступности (наличие на складе)
     */
    private function calculateAvailabilityScore(ProductListing $listing): float
    {
        if (!$listing->inStock) {
            return 0.0;
        }

        // Чем больше на складе, тем выше score (но с потолком)
        $stockScore = min($listing->stockQuantity / 50, 1.0);

        return $stockScore;
    }

    /**
     * Расчет promoted score
     * Платное продвижение
     */
    private function calculatePromotedScore(ProductListing $listing): float
    {
        return $listing->isPromoted ? 1.0 : 0.0;
    }

    /**
     * Расчет promotion boost score
     * Учитывает тип рекламы, бюджет, показы, клики и приоритет
     */
    private function calculatePromotionBoostScore(ProductListing $listing): float
    {
        if (!$listing->isPromotionActive()) {
            return 0.0;
        }

        // Проверяем превышение бюджета - если превышен, снижаем boost
        if ($listing->isPromotionBudgetExceeded()) {
            return 0.0;
        }

        // Базовый boost за активную рекламу
        $boost = 0.5;

        // Учет типа рекламы
        $promotionType = $listing->getPromotionType();
        if ($promotionType === 'homepage_hero') {
            $boost += 0.4; // Максимальный boost для hero позиции
        } elseif ($promotionType === 'homepage_banner') {
            $boost += 0.3;
        } elseif ($promotionType === 'category_top') {
            $boost += 0.2;
        } elseif ($promotionType === 'search_top') {
            $boost += 0.25;
        } elseif ($promotionType === 'feed_top') {
            $boost += 0.15;
        }

        // Учет бюджета (чем больше бюджет, тем выше boost)
        $budget = $listing->getPromotionBudget();
        if ($budget !== null && $budget > 0) {
            // Нормализация: 10000 RUB = максимальный boost
            $budgetBoost = min($budget / 10000.0, 0.3);
            $boost += $budgetBoost;
        }

        // Учет CTR (click-through rate) - чем выше CTR, тем выше boost
        $ctr = $listing->getPromotionCTR();
        if ($ctr > 0) {
            // Нормализация: 5% CTR = максимальный boost
            $ctrBoost = min($ctr / 0.05, 0.2);
            $boost += $ctrBoost;
        }

        // Учет количества показов (больше показов = более популярная реклама)
        $impressions = $listing->getPromotionImpressions();
        if ($impressions > 0) {
            // Нормализация: 1000 показов = максимальный boost
            $impressionBoost = min($impressions / 1000.0, 0.15);
            $boost += $impressionBoost;
        }

        // Учет promotion_priority_boost
        $promotionPriorityBoost = $listing->promotionPriorityBoost;
        if ($promotionPriorityBoost > 0) {
            $boost += min($promotionPriorityBoost / 100.0, 0.2);
        }

        // Учет priority_boost (legacy)
        $priorityBoost = $listing->priorityBoost;
        if ($priorityBoost > 0) {
            $boost += min($priorityBoost / 100.0, 0.1);
        }

        return min($boost, 1.0);
    }

    /**
     * ML score (placeholder для интеграции с ML сервисами)
     * В проде должен вызывать ML модель для предсказания CTR/конверсии
     */
    private function calculateMLScore(ProductListing $listing): float
    {
        // Placeholder: здесь должна быть интеграция с ML сервисом
        // Например, вызов Python ML сервиса через HTTP или gRPC

        // Временная реализация на основе эвристики
        $baseScore = 0.5;

        // Учитываем комбинацию факторов
        if ($listing->rating?->isExcellent() && $listing->inStock) {
            $baseScore += 0.2;
        }

        if ($listing->conversionRate > 0.05) {
            $baseScore += 0.15;
        }

        if ($listing->isFeatured) {
            $baseScore += 0.1;
        }

        return min($baseScore, 1.0);
    }

    /**
     * Personalization score (placeholder для персонализации)
     * В проде должен учитывать историю пользователя, предпочтения, поведение
     */
    private function calculatePersonalizationScore(ProductListing $listing): float
    {
        // Placeholder: здесь должна быть персонализация на основе user context
        // Например, collaborative filtering, content-based recommendations

        // Временная реализация: базовый score без персонализации
        return 0.0;
    }

    /**
     * Получить топ позиций по рейтингу
     */
    public function getTopRanked(int $limit = 100): array
    {
        $topScores = $this->rankingRepository->findTopScores($limit);
        $listings = [];

        foreach ($topScores as $scoreData) {
            $listing = $this->listingRepository->findByUuid(
                \Ramsey\Uuid\Uuid::fromString($scoreData['uuid'])
            );
            if ($listing !== null && $listing->isAvailable()) {
                $listings[] = $listing;
            }
        }

        return $listings;
    }

    /**
     * Обновить конфигурацию ранжирования
     */
    public function updateConfig(RankingConfigDTO $config): void
    {
        // В проде конфигурация должна храниться в БД или config service
        // Здесь просто логируем изменение
        $this->logger->info('Ranking config updated', [
            'algorithm_version' => $config->algorithmVersion,
            'enable_ml' => $config->enableML,
            'enable_personalization' => $config->enablePersonalization,
        ]);
    }

    /**
     * Получить статистику ранжирования
     */
    public function getRankingStats(): array
    {
        $activeListings = $this->listingRepository->findActive();
        $expiredScores = $this->rankingRepository->findExpired();

        $totalScore = 0.0;
        $minScore = 1.0;
        $maxScore = 0.0;

        foreach ($activeListings as $listing) {
            $score = $this->rankingRepository->findByListingUuid($listing->uuid);
            if ($score !== null) {
                $totalScore += $score->overallScore;
                $minScore = min($minScore, $score->overallScore);
                $maxScore = max($maxScore, $score->overallScore);
            }
        }

        $count = count($activeListings);
        $avgScore = $count > 0 ? $totalScore / $count : 0.0;

        return [
            'total_listings' => $count,
            'expired_scores' => count($expiredScores),
            'avg_score' => $avgScore,
            'min_score' => $minScore,
            'max_score' => $maxScore,
            'algorithm_version' => $this->config->algorithmVersion,
            'enable_ml' => $this->config->enableML,
            'enable_personalization' => $this->config->enablePersonalization,
        ];
    }
}
