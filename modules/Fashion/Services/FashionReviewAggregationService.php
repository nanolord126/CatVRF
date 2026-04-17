<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionReviewAggregationService
{
    private const CACHE_TTL = 3600;

    /**
     * Aggregate reviews for a product
     */
    public function aggregateProductReviews(int $productId, int $tenantId): array
    {
        $cacheKey = "fashion_reviews_aggregated:{$tenantId}:{$productId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($productId, $tenantId) {
            $reviews = DB::table('fashion_reviews')
                ->where('fashion_product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->where('is_approved', true)
                ->get();

            if ($reviews->isEmpty()) {
                return [
                    'average_rating' => 0,
                    'total_reviews' => 0,
                    'rating_distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
                    'sentiment' => 'neutral',
                ];
            }

            $averageRating = $reviews->avg('rating');
            $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

            foreach ($reviews as $review) {
                $distribution[$review->rating]++;
            }

            $sentiment = $this->determineSentiment($averageRating);

            return [
                'average_rating' => round($averageRating, 2),
                'total_reviews' => $reviews->count(),
                'rating_distribution' => $distribution,
                'sentiment' => $sentiment,
            ];
        });
    }

    /**
     * Determine sentiment based on rating
     */
    private function determineSentiment(float $rating): string
    {
        if ($rating >= 4.0) return 'positive';
        if ($rating >= 3.0) return 'neutral';
        return 'negative';
    }

    /**
     * Get helpful reviews
     */
    public function getHelpfulReviews(int $productId, int $tenantId, int $limit = 5): array
    {
        return DB::table('fashion_reviews')
            ->where('fashion_product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('is_approved', true)
            ->orderByDesc('helpful_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Mark review as helpful
     */
    public function markReviewHelpful(int $reviewId, int $userId, int $tenantId): bool
    {
        try {
            DB::table('fashion_review_helpfuls')->insert([
                'review_id' => $reviewId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'created_at' => Carbon::now(),
            ]);

            DB::table('fashion_reviews')
                ->where('id', $reviewId)
                ->increment('helpful_count');

            // Clear cache
            Cache::tags(["fashion_reviews:{$tenantId}"])->flush();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark review as helpful', [
                'review_id' => $reviewId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Moderate review (approve/reject)
     */
    public function moderateReview(int $reviewId, bool $approve, int $tenantId): bool
    {
        try {
            DB::table('fashion_reviews')
                ->where('id', $reviewId)
                ->where('tenant_id', $tenantId)
                ->update([
                    'is_approved' => $approve,
                    'moderated_at' => Carbon::now(),
                ]);

            // Clear cache
            Cache::tags(["fashion_reviews:{$tenantId}"])->flush();

            Log::info('Review moderated', [
                'review_id' => $reviewId,
                'approved' => $approve,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to moderate review', [
                'review_id' => $reviewId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get review insights for a store
     */
    public function getStoreReviewInsights(int $storeId, int $tenantId): array
    {
        $cacheKey = "fashion_store_insights:{$tenantId}:{$storeId}";

        return Cache::remember($cacheKey, Carbon::now()->addHours(6), function () use ($storeId, $tenantId) {
            $reviews = DB::table('fashion_reviews')
                ->join('fashion_products', 'fashion_reviews.fashion_product_id', '=', 'fashion_products.id')
                ->where('fashion_products.fashion_store_id', $storeId)
                ->where('fashion_reviews.tenant_id', $tenantId)
                ->where('fashion_reviews.is_approved', true)
                ->select('fashion_reviews.rating', 'fashion_reviews.created_at')
                ->get();

            if ($reviews->isEmpty()) {
                return [
                    'average_rating' => 0,
                    'total_reviews' => 0,
                    'recent_trend' => 'stable',
                ];
            }

            $averageRating = $reviews->avg('rating');
            $recentReviews = $reviews->where('created_at', '>=', Carbon::now()->subDays(30));
            $recentRating = $recentReviews->avg('rating') ?? $averageRating;

            $trend = abs($recentRating - $averageRating) < 0.3 ? 'stable' : ($recentRating > $averageRating ? 'improving' : 'declining');

            return [
                'average_rating' => round($averageRating, 2),
                'total_reviews' => $reviews->count(),
                'recent_trend' => $trend,
            ];
        });
    }
}
