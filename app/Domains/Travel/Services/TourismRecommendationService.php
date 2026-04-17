<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use App\Domains\Travel\Models\Tour;
use App\Domains\Travel\Models\TourismWishlist;
use App\Services\ML\UserTasteAnalyzerService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;

/**
 * Tourism Recommendation Service
 * 
 * AI-powered tour recommendation service with embeddings.
 * Uses UserTasteAnalyzerService for personalization and
 * Redis for caching recommendations.
 */
final readonly class TourismRecommendationService
{
    public function __construct(
        private UserTasteAnalyzerService $tasteAnalyzer,
        private LoggerInterface $logger,
    ) {}
    private Cache $cache,
        private RedisConnection $redis,
    
    /**
     * Get personalized tour recommendations for a user.
     * 
     * @param int $userId User ID
     * @param int $limit Number of recommendations to return
     * @param string $correlationId Correlation ID for tracing
     * @return array Array of recommended tours with scores
     */
    public function getPersonalizedRecommendations(int $userId, int $limit = 10, string $correlationId = ''): array
    {
        $cacheKey = "tourism_recommendations:{$userId}:{$limit}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            $this->logger->info('Tourism recommendations returned from cache', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            return $cached;
        }

        $wishlistItems = TourismWishlist::where('user_id', $userId)
            ->with('tour.destination')
            ->get();

        $wishlistDestinations = $wishlistItems->pluck('tour.destination.name')->filter()->unique()->toArray();
        $wishlistBudgetRanges = $wishlistItems->pluck('budget_range')->filter()->toArray();
        $wishlistDurations = $wishlistItems->pluck('tour.duration_days')->unique()->toArray();

        $tasteProfile = $this->tasteAnalyzer->getProfile($userId, 'tourism');

        $userPreferences = $tasteProfile->travel_preferences ?? [];
        $preferredDestinations = array_unique(array_merge(
            $userPreferences['destinations'] ?? [],
            $wishlistDestinations
        ));
        
        $budgetRange = $userPreferences['budget_range'] ?? [0, 1000000];
        
        if (!empty($wishlistBudgetRanges)) {
            $minBudget = min(array_column($wishlistBudgetRanges, 0));
            $maxBudget = max(array_column($wishlistBudgetRanges, 1));
            $budgetRange[0] = min($budgetRange[0], $minBudget);
            $budgetRange[1] = max($budgetRange[1], $maxBudget);
        }

        $durationPreference = $userPreferences['duration_days'] ?? [3, 14];
        
        if (!empty($wishlistDurations)) {
            $minDuration = min($wishlistDurations);
            $maxDuration = max($wishlistDurations);
            $durationPreference[0] = min($durationPreference[0], $minDuration - 2);
            $durationPreference[1] = max($durationPreference[1], $maxDuration + 2);
        }

        $query = Tour::where('is_active', true)
            ->where('base_price', '>=', $budgetRange[0])
            ->where('base_price', '<=', $budgetRange[1])
            ->where('duration_days', '>=', $durationPreference[0])
            ->where('duration_days', '<=', $durationPreference[1]);

        if (!empty($preferredDestinations)) {
            $query->whereHas('destination', function ($q) use ($preferredDestinations) {
                $q->whereIn('name', $preferredDestinations);
            });
        }

        $excludeTourIds = $wishlistItems->pluck('tour_id')->toArray();
        if (!empty($excludeTourIds)) {
            $query->whereNotIn('id', $excludeTourIds);
        }

        $tours = $query->with('destination')->get();

        $recommendations = [];
        foreach ($tours as $tour) {
            $score = $this->calculateRecommendationScore($tour, $userPreferences);
            
            if ($score > 0.3) {
                $recommendations[] = [
                    'tour_id' => $tour->id,
                    'tour_uuid' => $tour->uuid,
                    'title' => $tour->title,
                    'destination' => $tour->destination->name ?? 'Unknown',
                    'base_price' => (float) $tour->base_price,
                    'duration_days' => $tour->duration_days,
                    'difficulty' => $tour->difficulty,
                    'score' => $score,
                    'match_reasons' => $this->getMatchReasons($tour, $userPreferences),
                ];
            }
        }

        usort($recommendations, fn ($a, $b) => $b['score'] <=> $a['score']);
        $recommendations = array_slice($recommendations, 0, $limit);

        $this->cache->put($cacheKey, $recommendations, 3600);

        $this->logger->info('Tourism recommendations generated', [
            'user_id' => $userId,
            'recommendations_count' => count($recommendations),
            'correlation_id' => $correlationId,
        ]);

        return $recommendations;
    }

    /**
     * Get flash sale tours with dynamic pricing.
     * 
     * @param int $limit Number of flash sales to return
     * @param string $correlationId Correlation ID for tracing
     * @return array Array of flash sale tours
     */
    public function getFlashSales(int $limit = 5, string $correlationId = ''): array
    {
        $cacheKey = "tourism_flash_sales:{$limit}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $flashSales = Tour::where('is_active', true)
            ->where('discount_enabled', true)
            ->where('discount_ends_at', '>', now())
            ->where('discount_percentage', '>', 0)
            ->with('destination')
            ->orderBy('discount_percentage', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($tour) {
                return [
                    'tour_id' => $tour->id,
                    'tour_uuid' => $tour->uuid,
                    'title' => $tour->title,
                    'destination' => $tour->destination->name ?? 'Unknown',
                    'original_price' => (float) $tour->base_price,
                    'discount_percentage' => (float) $tour->discount_percentage,
                    'discounted_price' => (float) ($tour->base_price * (1 - $tour->discount_percentage / 100)),
                    'discount_ends_at' => $tour->discount_ends_at?->toIso8601String(),
                    'time_remaining' => $tour->discount_ends_at ? now()->diffInSeconds($tour->discount_ends_at) : null,
                ];
            })
            ->toArray();

        $this->cache->put($cacheKey, $flashSales, 300);

        $this->logger->info('Tourism flash sales retrieved', [
            'flash_sales_count' => count($flashSales),
            'correlation_id' => $correlationId,
        ]);

        return $flashSales;
    }

    /**
     * Get trending tours based on recent bookings.
     * 
     * @param int $limit Number of trending tours to return
     * @param string $correlationId Correlation ID for tracing
     * @return array Array of trending tours
     */
    public function getTrendingTours(int $limit = 10, string $correlationId = ''): array
    {
        $cacheKey = "tourism_trending:{$limit}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $trendingTours = Tour::where('is_active', true)
            ->with('destination')
            ->withCount('bookings')
            ->orderBy('bookings_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($tour) {
                return [
                    'tour_id' => $tour->id,
                    'tour_uuid' => $tour->uuid,
                    'title' => $tour->title,
                    'destination' => $tour->destination->name ?? 'Unknown',
                    'base_price' => (float) $tour->base_price,
                    'duration_days' => $tour->duration_days,
                    'difficulty' => $tour->difficulty,
                    'bookings_count' => $tour->bookings_count,
                    'rating' => $tour->rating ?? 4.5,
                ];
            })
            ->toArray();

        $this->cache->put($cacheKey, $trendingTours, 1800);

        $this->logger->info('Tourism trending tours retrieved', [
            'trending_count' => count($trendingTours),
            'correlation_id' => $correlationId,
        ]);

        return $trendingTours;
    }

    /**
     * Calculate recommendation score for a tour based on user preferences.
     */
    private function calculateRecommendationScore(Tour $tour, array $userPreferences): float
    {
        $score = 0.5;

        $preferredDestinations = $userPreferences['destinations'] ?? [];
        $preferredActivities = $userPreferences['activities'] ?? [];

        if (in_array($tour->destination->name ?? '', $preferredDestinations)) {
            $score += 0.3;
        }

        $tourActivities = $tour->tags ?? [];
        $matchingActivities = array_intersect($tourActivities, $preferredActivities);
        if (!empty($matchingActivities)) {
            $score += 0.2 * (count($matchingActivities) / max(count($preferredActivities), 1));
        }

        if ($tour->rating >= 4.5) {
            $score += 0.1;
        }

        return min($score, 1.0);
    }

    /**
     * Get reasons why a tour matches user preferences.
     */
    private function getMatchReasons(Tour $tour, array $userPreferences): array
    {
        $reasons = [];

        $preferredDestinations = $userPreferences['destinations'] ?? [];
        if (in_array($tour->destination->name ?? '', $preferredDestinations)) {
            $reasons[] = 'matches your preferred destination';
        }

        if ($tour->rating >= 4.5) {
            $reasons[] = 'highly rated by other travelers';
        }

        $tourActivities = $tour->tags ?? [];
        $preferredActivities = $userPreferences['activities'] ?? [];
        $matchingActivities = array_intersect($tourActivities, $preferredActivities);
        if (!empty($matchingActivities)) {
            $reasons[] = 'includes your preferred activities';
        }

        return $reasons;
    }
}
