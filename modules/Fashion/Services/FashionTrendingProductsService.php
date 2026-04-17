<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionTrendingProductsService
{
    private const CACHE_TTL = 3600;

    /**
     * Get trending products based on views, purchases, and social media mentions
     */
    public function getTrendingProducts(int $tenantId, int $limit = 20, string $period = '7d'): array
    {
        $cacheKey = "fashion_trending:{$tenantId}:{$period}:{$limit}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($tenantId, $limit, $period) {
            $startDate = match($period) {
                '1d' => Carbon::now()->subDay(),
                '7d' => Carbon::now()->subDays(7),
                '30d' => Carbon::now()->subDays(30),
                default => Carbon::now()->subDays(7),
            };

            // Get products with engagement metrics
            $products = DB::table('fashion_products')
                ->leftJoin('fashion_product_analytics', 'fashion_products.id', '=', 'fashion_product_analytics.fashion_product_id')
                ->leftJoin('fashion_trend_scores', 'fashion_products.id', '=', 'fashion_trend_scores.fashion_product_id')
                ->leftJoin('fashion_social_mentions', 'fashion_products.id', '=', 'fashion_social_mentions.fashion_product_id')
                ->where('fashion_products.tenant_id', $tenantId)
                ->where('fashion_products.status', 'active')
                ->where('fashion_products.available_stock', '>', 0)
                ->where(function ($query) use ($startDate) {
                    $query->where('fashion_products.created_at', '>=', $startDate)
                          ->orWhere('fashion_product_analytics.updated_at', '>=', $startDate);
                })
                ->select(
                    'fashion_products.id',
                    'fashion_products.name',
                    'fashion_products.price_b2c',
                    'fashion_products.old_price',
                    'fashion_products.image_url',
                    'fashion_products.brand_id',
                    DB::raw('COALESCE(fashion_product_analytics.views, 0) as views'),
                    DB::raw('COALESCE(fashion_product_analytics.add_to_cart, 0) as add_to_cart'),
                    DB::raw('COALESCE(fashion_product_analytics.purchases, 0) as purchases'),
                    DB::raw('COALESCE(fashion_trend_scores.score, 0) as trend_score'),
                    DB::raw('COALESCE(fashion_social_mentions.mentions_count, 0) as social_mentions')
                )
                ->orderByDesc('trend_score')
                ->limit($limit)
                ->get()
                ->toArray();

            // Calculate trending score for each product
            $trendingProducts = array_map(function ($product) {
                $product['trending_score'] = $this->calculateTrendingScore($product);
                return $product;
            }, $products);

            // Sort by trending score
            usort($trendingProducts, function ($a, $b) {
                return $b['trending_score'] <=> $a['trending_score'];
            });

            return $trendingProducts;
        });
    }

    /**
     * Calculate trending score based on multiple factors
     */
    private function calculateTrendingScore(array $product): float
    {
        $weights = [
            'views' => 0.3,
            'add_to_cart' => 0.25,
            'purchases' => 0.25,
            'trend_score' => 0.15,
            'social_mentions' => 0.05,
        ];

        // Normalize values (simple approach)
        $maxViews = 10000;
        $maxAddToCart = 1000;
        $maxPurchases = 500;
        $maxTrendScore = 1.0;
        $maxSocialMentions = 1000;

        $normalizedViews = min($product['views'] / $maxViews, 1.0);
        $normalizedAddToCart = min($product['add_to_cart'] / $maxAddToCart, 1.0);
        $normalizedPurchases = min($product['purchases'] / $maxPurchases, 1.0);
        $normalizedTrendScore = min($product['trend_score'] / $maxTrendScore, 1.0);
        $normalizedSocialMentions = min($product['social_mentions'] / $maxSocialMentions, 1.0);

        $trendingScore =
            ($normalizedViews * $weights['views']) +
            ($normalizedAddToCart * $weights['add_to_cart']) +
            ($normalizedPurchases * $weights['purchases']) +
            ($normalizedTrendScore * $weights['trend_score']) +
            ($normalizedSocialMentions * $weights['social_mentions']);

        return round($trendingScore * 100, 2);
    }

    /**
     * Get trending products by category
     */
    public function getTrendingProductsByCategory(int $tenantId, int $categoryId, int $limit = 10): array
    {
        $cacheKey = "fashion_trending_category:{$tenantId}:{$categoryId}:{$limit}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($tenantId, $categoryId, $limit) {
            $products = DB::table('fashion_products')
                ->join('fashion_product_categories', 'fashion_products.id', '=', 'fashion_product_categories.fashion_product_id')
                ->leftJoin('fashion_product_analytics', 'fashion_products.id', '=', 'fashion_product_analytics.fashion_product_id')
                ->leftJoin('fashion_trend_scores', 'fashion_products.id', '=', 'fashion_trend_scores.fashion_product_id')
                ->where('fashion_products.tenant_id', $tenantId)
                ->where('fashion_product_categories.fashion_category_id', $categoryId)
                ->where('fashion_products.status', 'active')
                ->where('fashion_products.available_stock', '>', 0)
                ->select(
                    'fashion_products.id',
                    'fashion_products.name',
                    'fashion_products.price_b2c',
                    'fashion_products.image_url',
                    DB::raw('COALESCE(fashion_product_analytics.views, 0) as views'),
                    DB::raw('COALESCE(fashion_trend_scores.score, 0) as trend_score')
                )
                ->orderByDesc('fashion_trend_scores.score')
                ->limit($limit)
                ->get()
                ->toArray();

            return array_map(function ($product) {
                $product['trending_score'] = $this->calculateTrendingScore($product);
                return $product;
            }, $products);
        });
    }

    /**
     * Update trend scores for products
     */
    public function updateTrendScores(int $tenantId): void
    {
        $products = DB::table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get();

        foreach ($products as $product) {
            $trendScore = $this->calculateProductTrendScore($product->id, $tenantId);

            DB::table('fashion_trend_scores')->updateOrInsert(
                ['fashion_product_id' => $product->id, 'tenant_id' => $tenantId],
                [
                    'score' => $trendScore,
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        // Clear trending cache
        Cache::tags(["fashion_trending:{$tenantId}"])->flush();

        Log::info('Fashion trend scores updated', [
            'tenant_id' => $tenantId,
            'products_count' => $products->count(),
        ]);
    }

    /**
     * Calculate trend score for a specific product
     */
    private function calculateProductTrendScore(int $productId, int $tenantId): float
    {
        $analytics = DB::table('fashion_product_analytics')
            ->where('fashion_product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$analytics) {
            return 0.0;
        }

        // Calculate velocity (change over time)
        $velocity = ($analytics->purchases * 2) + $analytics->add_to_cart + ($analytics->views * 0.1);

        // Normalize to 0-1 range
        return min($velocity / 1000, 1.0);
    }
}
