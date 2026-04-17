<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionRecommendationEngineService
{
    private const CACHE_TTL = 1800;

    /**
     * Get personalized product recommendations for a user
     */
    public function getPersonalizedRecommendations(int $userId, int $tenantId, int $limit = 10): array
    {
        $cacheKey = "fashion_recommendations:{$tenantId}:{$userId}:{$limit}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($userId, $tenantId, $limit) {
            // Get user's purchase history
            $purchasedCategories = DB::table('fashion_order_items')
                ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
                ->join('fashion_product_categories', 'fashion_products.id', '=', 'fashion_product_categories.fashion_product_id')
                ->join('fashion_categories', 'fashion_product_categories.fashion_category_id', '=', 'fashion_categories.id')
                ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
                ->where('fashion_orders.user_id', $userId)
                ->where('fashion_orders.tenant_id', $tenantId)
                ->where('fashion_orders.status', 'completed')
                ->distinct()
                ->pluck('fashion_categories.id')
                ->toArray();

            // Get user's wishlist
            $wishlistProductIds = DB::table('fashion_wishlists')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->pluck('fashion_product_id')
                ->toArray();

            // Get collaborative filtering recommendations
            $collaborativeRecs = $this->getCollaborativeFilteringRecommendations($userId, $tenantId, $purchasedCategories);

            // Get content-based recommendations
            $contentRecs = $this->getContentBasedRecommendations($userId, $tenantId, $purchasedCategories, $wishlistProductIds);

            // Get trending products
            $trendingRecs = $this->getTrendingProducts($tenantId);

            // Merge and score recommendations
            $recommendations = $this->mergeAndScoreRecommendations($collaborativeRecs, $contentRecs, $trendingRecs);

            // Remove already purchased and wishlist items
            $purchasedProductIds = DB::table('fashion_order_items')
                ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
                ->where('fashion_orders.user_id', $userId)
                ->where('fashion_orders.tenant_id', $tenantId)
                ->pluck('fashion_order_items.fashion_product_id')
                ->toArray();

            $excludeIds = array_merge($purchasedProductIds, $wishlistProductIds);

            $recommendations = array_filter($recommendations, function ($rec) use ($excludeIds) {
                return !in_array($rec['product_id'], $excludeIds);
            });

            // Sort by score and limit
            usort($recommendations, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            return array_slice($recommendations, 0, $limit);
        });
    }

    /**
     * Get collaborative filtering recommendations
     */
    private function getCollaborativeFilteringRecommendations(int $userId, int $tenantId, array $purchasedCategories): array
    {
        if (empty($purchasedCategories)) {
            return [];
        }

        // Find users with similar purchase patterns
        $similarUsers = DB::table('fashion_order_items')
            ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
            ->join('fashion_product_categories', 'fashion_order_items.fashion_product_id', '=', 'fashion_product_categories.fashion_product_id')
            ->whereIn('fashion_product_categories.fashion_category_id', $purchasedCategories)
            ->where('fashion_orders.user_id', '!=', $userId)
            ->where('fashion_orders.tenant_id', $tenantId)
            ->select('fashion_orders.user_id', DB::raw('COUNT(*) as common_purchases'))
            ->groupBy('fashion_orders.user_id')
            ->orderByDesc('common_purchases')
            ->limit(10)
            ->pluck('fashion_orders.user_id')
            ->toArray();

        if (empty($similarUsers)) {
            return [];
        }

        // Get products purchased by similar users
        $products = DB::table('fashion_order_items')
            ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
            ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
            ->whereIn('fashion_orders.user_id', $similarUsers)
            ->where('fashion_orders.tenant_id', $tenantId)
            ->where('fashion_products.available_stock', '>', 0)
            ->select('fashion_products.id as product_id', 'fashion_products.name', 'fashion_products.price_b2c', DB::raw('COUNT(*) as purchase_count'))
            ->groupBy('fashion_products.id', 'fashion_products.name', 'fashion_products.price_b2c')
            ->orderByDesc('purchase_count')
            ->limit(20)
            ->get()
            ->toArray();

        return array_map(function ($product) {
            return [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->price_b2c,
                'score' => $product->purchase_count * 0.4,
                'source' => 'collaborative',
            ];
        }, $products);
    }

    /**
     * Get content-based recommendations
     */
    private function getContentBasedRecommendations(int $userId, int $tenantId, array $purchasedCategories, array $wishlistProductIds): array
    {
        if (empty($purchasedCategories)) {
            return [];
        }

        $products = DB::table('fashion_products')
            ->join('fashion_product_categories', 'fashion_products.id', '=', 'fashion_product_categories.fashion_product_id')
            ->whereIn('fashion_product_categories.fashion_category_id', $purchasedCategories)
            ->where('fashion_products.tenant_id', $tenantId)
            ->where('fashion_products.available_stock', '>', 0)
            ->where('fashion_products.status', 'active')
            ->whereNotIn('fashion_products.id', $wishlistProductIds)
            ->select('fashion_products.id as product_id', 'fashion_products.name', 'fashion_products.price_b2c', DB::raw('AVG(fashion_products.rating) as avg_rating'))
            ->groupBy('fashion_products.id', 'fashion_products.name', 'fashion_products.price_b2c')
            ->orderByDesc('avg_rating')
            ->limit(20)
            ->get()
            ->toArray();

        return array_map(function ($product) {
            return [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->price_b2c,
                'score' => ($product->avg_rating ?? 0) * 0.3,
                'source' => 'content',
            ];
        }, $products);
    }

    /**
     * Get trending products
     */
    private function getTrendingProducts(int $tenantId): array
    {
        $products = DB::table('fashion_products')
            ->join('fashion_product_analytics', 'fashion_products.id', '=', 'fashion_product_analytics.fashion_product_id')
            ->where('fashion_products.tenant_id', $tenantId)
            ->where('fashion_products.available_stock', '>', 0)
            ->where('fashion_products.status', 'active')
            ->where('fashion_products.created_at', '>=', Carbon::now()->subDays(30))
            ->select('fashion_products.id as product_id', 'fashion_products.name', 'fashion_products.price_b2c', 'fashion_product_analytics.views', 'fashion_product_analytics.purchases')
            ->orderByDesc('fashion_product_analytics.views')
            ->limit(15)
            ->get()
            ->toArray();

        return array_map(function ($product) {
            $trendScore = ($product->views * 0.7) + ($product->purchases * 0.3);
            return [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->price_b2c,
                'score' => $trendScore * 0.3,
                'source' => 'trending',
            ];
        }, $products);
    }

    /**
     * Merge and score recommendations from different sources
     */
    private function mergeAndScoreRecommendations(array $collaborative, array $content, array $trending): array
    {
        $merged = [];

        foreach ($collaborative as $rec) {
            $productId = $rec['product_id'];
            if (!isset($merged[$productId])) {
                $merged[$productId] = $rec;
            } else {
                $merged[$productId]['score'] += $rec['score'];
            }
        }

        foreach ($content as $rec) {
            $productId = $rec['product_id'];
            if (!isset($merged[$productId])) {
                $merged[$productId] = $rec;
            } else {
                $merged[$productId]['score'] += $rec['score'];
            }
        }

        foreach ($trending as $rec) {
            $productId = $rec['product_id'];
            if (!isset($merged[$productId])) {
                $merged[$productId] = $rec;
            } else {
                $merged[$productId]['score'] += $rec['score'];
            }
        }

        return array_values($merged);
    }
}
