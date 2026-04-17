<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use App\Domains\Travel\Models\TourismWishlist;
use App\Domains\Travel\Models\Tour;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\AuditService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;

/**
 * Tourism Wishlist Service
 * 
 * Service for managing tourism wishlists with AI-powered recommendations.
 * When a user adds a tour to wishlist, automatically generates personalized
 * recommendations based on that tour and user preferences.
 */
final readonly class TourismWishlistService
{
    public function __construct(
        private UserTasteAnalyzerService $tasteAnalyzer,
        private AuditService $audit,
        private TourismRecommendationService $recommendationService,
        private LoggerInterface $logger,
        private Cache $cache,
        private RedisConnection $redis,
    ) {}

    /**
     * Add tour to wishlist and generate AI recommendations.
     * 
     * @param int $userId User ID
     * @param int $tourId Tour ID
     * @param array $preferences User preferences (budget, dates, group size, notes)
     * @param string $correlationId Correlation ID for tracing
     * @return TourismWishlist Created wishlist item
     */
    public function addToWishlist(int $userId, int $tourId, array $preferences = [], string $correlationId = ''): TourismWishlist
    {
        $tour = Tour::findOrFail($tourId);

        $wishlistItem = TourismWishlist::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
            'user_id' => $userId,
            'tour_id' => $tourId,
            'priority' => $preferences['priority'] ?? 5,
            'notes' => $preferences['notes'] ?? null,
            'budget_range' => $preferences['budget_range'] ?? null,
            'preferred_dates' => $preferences['preferred_dates'] ?? null,
            'group_size' => $preferences['group_size'] ?? null,
            'special_requests' => $preferences['special_requests'] ?? null,
            'metadata' => [
                'tour_title' => $tour->title,
                'tour_destination' => $tour->destination->name ?? 'Unknown',
                'tour_price' => $tour->base_price,
                'tour_duration' => $tour->duration_days,
                'added_from' => $preferences['added_from'] ?? 'manual',
            ],
        ]);

        $this->audit->record(
            action: 'tourism_wishlist_added',
            subjectType: TourismWishlist::class,
            subjectId: $wishlistItem->id,
            oldValues: [],
            newValues: [
                'user_id' => $userId,
                'tour_id' => $tourId,
                'tour_title' => $tour->title,
                'priority' => $wishlistItem->priority,
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('Tour added to wishlist', [
            'wishlist_id' => $wishlistItem->id,
            'user_id' => $userId,
            'tour_id' => $tourId,
            'tour_title' => $tour->title,
            'correlation_id' => $correlationId,
        ]);

        $this->generateRecommendationsFromWishlist($userId, $wishlistItem, $correlationId);

        return $wishlistItem;
    }

    /**
     * Remove tour from wishlist.
     * 
     * @param string $uuid Wishlist item UUID
     * @param string $correlationId Correlation ID for tracing
     * @return void
     */
    public function removeFromWishlist(string $uuid, string $correlationId = ''): void
    {
        $wishlistItem = TourismWishlist::where('uuid', $uuid)->firstOrFail();

        $tour = $wishlistItem->tour;

        $this->audit->record(
            action: 'tourism_wishlist_removed',
            subjectType: TourismWishlist::class,
            subjectId: $wishlistItem->id,
            oldValues: [
                'user_id' => $wishlistItem->user_id,
                'tour_id' => $wishlistItem->tour_id,
                'tour_title' => $tour->title,
            ],
            newValues: [],
            correlationId: $correlationId,
        );

        $wishlistItem->delete();

        $this->logger->info('Tour removed from wishlist', [
            'wishlist_id' => $wishlistItem->id,
            'user_id' => $wishlistItem->user_id,
            'tour_id' => $wishlistItem->tour_id,
            'correlation_id' => $correlationId,
        ]);

        $this->clearRecommendationsCache($wishlistItem->user_id);
    }

    /**
     * Get user wishlist with AI-powered recommendations.
     * 
     * @param int $userId User ID
     * @param string $correlationId Correlation ID for tracing
     * @return array Wishlist items with recommendations
     */
    public function getUserWishlist(int $userId, string $correlationId = ''): array
    {
        $cacheKey = "tourism_wishlist:{$userId}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $wishlistItems = TourismWishlist::where('user_id', $userId)
            ->with('tour.destination')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'uuid' => $item->uuid,
                    'tour_id' => $item->tour_id,
                    'tour_uuid' => $item->tour->uuid,
                    'tour_title' => $item->tour->title,
                    'tour_destination' => $item->tour->destination->name ?? 'Unknown',
                    'tour_price' => (float) $item->tour->base_price,
                    'tour_duration' => $item->tour->duration_days,
                    'priority' => $item->priority,
                    'notes' => $item->notes,
                    'budget_range' => $item->budget_range,
                    'preferred_dates' => $item->preferred_dates,
                    'group_size' => $item->group_size,
                    'special_requests' => $item->special_requests,
                    'is_high_priority' => $item->isHighPriority(),
                    'has_budget' => $item->hasBudget(),
                    'has_preferred_dates' => $item->hasPreferredDates(),
                    'added_at' => $item->created_at->toIso8601String(),
                ];
            })
            ->toArray();

        $recommendations = $this->getRecommendationsFromWishlist($userId, $correlationId);

        $this->cache->put($cacheKey, [
            'wishlist_items' => $wishlistItems,
            'recommendations' => $recommendations,
        ], 600);

        return [
            'wishlist_items' => $wishlistItems,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Get personalized recommendations based on wishlist items.
     * 
     * @param int $userId User ID
     * @param string $correlationId Correlation ID for tracing
     * @return array Personalized recommendations
     */
    public function getRecommendationsFromWishlist(int $userId, string $correlationId = ''): array
    {
        $wishlistItems = TourismWishlist::where('user_id', $userId)
            ->with('tour')
            ->get();

        if ($wishlistItems->isEmpty()) {
            return $this->recommendationService->getPersonalizedRecommendations($userId, 10, $correlationId);
        }

        $budgetRanges = $wishlistItems->pluck('budget_range')->filter()->toArray();
        $preferredDates = $wishlistItems->pluck('preferred_dates')->filter()->flatten()->unique()->toArray();
        $groupSizes = $wishlistItems->pluck('group_size')->filter()->toArray();

        $avgBudget = !empty($budgetRanges) ? [
            min(array_column($budgetRanges, 0)),
            max(array_column($budgetRanges, 1)),
        ] : null;

        $avgGroupSize = !empty($groupSizes) ? (int) round(array_sum($groupSizes) / count($groupSizes)) : null;

        $recommendations = [];
        foreach ($wishlistItems as $wishlistItem) {
            $tour = $wishlistItem->tour;
            $similarTours = $this->findSimilarTours($tour, $userId, $wishlistItem);

            $recommendations = array_merge($recommendations, $similarTours);
        }

        $recommendations = array_unique($recommendations, SORT_REGULAR);
        usort($recommendations, fn ($a, $b) => $b['score'] <=> $a['score']);
        $recommendations = array_slice($recommendations, 0, 10);

        $this->logger->info('Wishlist-based recommendations generated', [
            'user_id' => $userId,
            'wishlist_items_count' => $wishlistItems->count(),
            'recommendations_count' => count($recommendations),
            'correlation_id' => $correlationId,
        ]);

        return $recommendations;
    }

    /**
     * Generate recommendations when tour is added to wishlist.
     */
    private function generateRecommendationsFromWishlist(int $userId, TourismWishlist $wishlistItem, string $correlationId = ''): void
    {
        $tour = $wishlistItem->tour;
        $similarTours = $this->findSimilarTours($tour, $userId, $wishlistItem);

        $recommendationsKey = "tourism_wishlist_recommendations:{$userId}";
        $this->redis->setex($recommendationsKey, 3600, json_encode($similarTours));

        $this->logger->info('Wishlist recommendations generated on add', [
            'user_id' => $userId,
            'tour_id' => $tour->id,
            'similar_tours_count' => count($similarTours),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Find similar tours based on wishlist item.
     */
    private function findSimilarTours(Tour $tour, int $userId, TourismWishlist $wishlistItem): array
    {
        $query = Tour::where('id', '!=', $tour->id)
            ->where('is_active', true)
            ->where('destination_id', $tour->destination_id)
            ->with('destination');

        if ($wishlistItem->budget_range) {
            $query->whereBetween('base_price', $wishlistItem->budget_range);
        }

        if ($wishlistItem->group_size) {
            $query->where('max_group_size', '>=', $wishlistItem->group_size);
        }

        $similarTours = $query->limit(5)->get();

        $recommendations = [];
        foreach ($similarTours as $similarTour) {
            $score = $this->calculateSimilarityScore($tour, $similarTour, $wishlistItem);

            if ($score > 0.4) {
                $recommendations[] = [
                    'tour_id' => $similarTour->id,
                    'tour_uuid' => $similarTour->uuid,
                    'title' => $similarTour->title,
                    'destination' => $similarTour->destination->name ?? 'Unknown',
                    'base_price' => (float) $similarTour->base_price,
                    'duration_days' => $similarTour->duration_days,
                    'difficulty' => $similarTour->difficulty,
                    'score' => $score,
                    'from_wishlist_tour' => $tour->title,
                    'match_reasons' => $this->getMatchReasons($tour, $similarTour, $wishlistItem),
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Calculate similarity score between two tours.
     */
    private function calculateSimilarityScore(Tour $originalTour, Tour $similarTour, TourismWishlist $wishlistItem): float
    {
        $score = 0.5;

        if ($originalTour->destination_id === $similarTour->destination_id) {
            $score += 0.3;
        }

        if (abs($originalTour->duration_days - $similarTour->duration_days) <= 2) {
            $score += 0.1;
        }

        if ($originalTour->difficulty === $similarTour->difficulty) {
            $score += 0.1;
        }

        if ($wishlistItem->budget_range) {
            $tourPrice = $similarTour->base_price;
            $minBudget = $wishlistItem->budget_range[0];
            $maxBudget = $wishlistItem->budget_range[1];

            if ($tourPrice >= $minBudget && $tourPrice <= $maxBudget) {
                $score += 0.2;
            }
        }

        return min($score, 1.0);
    }

    /**
     * Get match reasons for recommendation.
     */
    private function getMatchReasons(Tour $originalTour, Tour $similarTour, TourismWishlist $wishlistItem): array
    {
        $reasons = [];

        if ($originalTour->destination_id === $similarTour->destination_id) {
            $reasons[] = 'same destination as your wishlist item';
        }

        if (abs($originalTour->duration_days - $similarTour->duration_days) <= 2) {
            $reasons[] = 'similar duration';
        }

        if ($originalTour->difficulty === $similarTour->difficulty) {
            $reasons[] = 'same difficulty level';
        }

        if ($wishlistItem->budget_range && $similarTour->base_price >= $wishlistItem->budget_range[0] && $similarTour->base_price <= $wishlistItem->budget_range[1]) {
            $reasons[] = 'fits your budget';
        }

        return $reasons;
    }

    /**
     * Clear recommendations cache for user.
     */
    private function clearRecommendationsCache(int $userId): void
    {
        $this->cache->forget("tourism_wishlist:{$userId}");
        $this->redis->del("tourism_wishlist_recommendations:{$userId}");
    }

    /**
     * Calculate wishlist-based discount for booking.
     * 
     * @param int $userId User ID
     * @param int $tourId Tour ID
     * @param string $correlationId Correlation ID for tracing
     * @return float Discount rate (0-1)
     */
    public function getWishlistDiscount(int $userId, int $tourId, string $correlationId = ''): float
    {
        $wishlistItem = TourismWishlist::where('user_id', $userId)
            ->where('tour_id', $tourId)
            ->first();

        if (!$wishlistItem) {
            return 0;
        }

        if ($wishlistItem->isHighPriority()) {
            return 0.05; // 5% discount for high priority wishlist items
        }

        $wishlistCount = TourismWishlist::where('user_id', $userId)->count();

        if ($wishlistCount >= 5) {
            return 0.03; // 3% discount for loyal users with 5+ wishlist items
        }

        return 0;
    }
}
