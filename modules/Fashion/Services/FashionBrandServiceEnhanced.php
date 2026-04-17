<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionBrandServiceEnhanced
{
    private const CACHE_TTL = 7200;

    /**
     * Get brand analytics dashboard data
     */
    public function getBrandAnalytics(int $brandId, int $tenantId): array
    {
        $cacheKey = "fashion_brand_analytics:{$tenantId}:{$brandId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($brandId, $tenantId) {
            $brand = DB::table('fashion_brands')
                ->where('id', $brandId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$brand) {
                return ['error' => 'Brand not found'];
            }

            // Get product count
            $productCount = DB::table('fashion_products')
                ->where('brand_id', $brandId)
                ->where('tenant_id', $tenantId)
                ->count();

            // Get total sales
            $totalSales = DB::table('fashion_order_items')
                ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
                ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
                ->where('fashion_products.brand_id', $brandId)
                ->where('fashion_orders.tenant_id', $tenantId)
                ->where('fashion_orders.status', 'completed')
                ->sum(DB::raw('fashion_order_items.quantity * fashion_order_items.price'));

            // Get average rating
            $avgRating = DB::table('fashion_products')
                ->where('brand_id', $brandId)
                ->where('tenant_id', $tenantId)
                ->avg('rating') ?? 0;

            // Get top performing products
            $topProducts = DB::table('fashion_products')
                ->where('brand_id', $brandId)
                ->where('tenant_id', $tenantId)
                ->orderByDesc('sales_count')
                ->limit(5)
                ->select('id', 'name', 'sales_count', 'rating')
                ->get()
                ->toArray();

            // Get sales trend (last 30 days)
            $salesTrend = $this->getSalesTrend($brandId, $tenantId);

            return [
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'product_count' => $productCount,
                'total_sales' => $totalSales,
                'average_rating' => round($avgRating, 2),
                'top_products' => $topProducts,
                'sales_trend' => $salesTrend,
            ];
        });
    }

    /**
     * Get sales trend for a brand
     */
    private function getSalesTrend(int $brandId, int $tenantId): array
    {
        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $sales = DB::table('fashion_order_items')
                ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
                ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
                ->where('fashion_products.brand_id', $brandId)
                ->where('fashion_orders.tenant_id', $tenantId)
                ->where('fashion_orders.status', 'completed')
                ->whereDate('fashion_orders.created_at', $date)
                ->sum(DB::raw('fashion_order_items.quantity * fashion_order_items.price'));

            $trend[] = [
                'date' => $date->toDateString(),
                'sales' => $sales,
            ];
        }

        return $trend;
    }

    /**
     * Get brand fit profiles
     */
    public function getBrandFitProfiles(int $brandId, int $tenantId): array
    {
        return DB::table('fashion_brand_fit_profiles')
            ->where('brand_id', $brandId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->toArray();
    }

    /**
     * Create or update brand fit profile
     */
    public function updateBrandFitProfile(int $brandId, int $tenantId, array $fitData): bool
    {
        try {
            DB::table('fashion_brand_fit_profiles')->updateOrInsert(
                ['brand_id' => $brandId, 'tenant_id' => $tenantId],
                [
                    'size_consistency' => $fitData['size_consistency'] ?? null,
                    'fit_accuracy' => $fitData['fit_accuracy'] ?? null,
                    'customer_satisfaction' => $fitData['customer_satisfaction'] ?? null,
                    'return_rate' => $fitData['return_rate'] ?? null,
                    'updated_at' => Carbon::now(),
                ]
            );

            Log::info('Brand fit profile updated', [
                'brand_id' => $brandId,
                'tenant_id' => $tenantId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update brand fit profile', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get brand sustainability score
     */
    public function getSustainabilityScore(int $brandId, int $tenantId): array
    {
        $score = DB::table('fashion_sustainability_scores')
            ->where('brand_id', $brandId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$score) {
            return [
                'overall_score' => 0,
                'environmental_score' => 0,
                'social_score' => 0,
                'governance_score' => 0,
            ];
        }

        return [
            'overall_score' => $score->overall_score,
            'environmental_score' => $score->environmental_score,
            'social_score' => $score->social_score,
            'governance_score' => $score->governance_score,
            'certifications' => json_decode($score->certifications ?? '[]', true),
        ];
    }

    /**
     * Compare brands
     */
    public function compareBrands(array $brandIds, int $tenantId): array
    {
        $brands = DB::table('fashion_brands')
            ->whereIn('id', $brandIds)
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('id');

        $comparison = [];

        foreach ($brandIds as $brandId) {
            if (!$brands->has($brandId)) {
                continue;
            }

            $brand = $brands->get($brandId);
            $analytics = $this->getBrandAnalytics($brandId, $tenantId);
            $sustainability = $this->getSustainabilityScore($brandId, $tenantId);

            $comparison[] = [
                'brand_id' => $brandId,
                'name' => $brand->name,
                'analytics' => $analytics,
                'sustainability' => $sustainability,
            ];
        }

        return $comparison;
    }

    /**
     * Get brand recommendations for users
     */
    public function getBrandRecommendations(int $userId, int $tenantId, int $limit = 5): array
    {
        // Get brands from user's purchase history
        $purchasedBrandIds = DB::table('fashion_order_items')
            ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
            ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
            ->where('fashion_orders.user_id', $userId)
            ->where('fashion_orders.tenant_id', $tenantId)
            ->where('fashion_orders.status', 'completed')
            ->distinct()
            ->pluck('fashion_products.brand_id')
            ->toArray();

        // Get similar brands (same category, similar price range)
        $similarBrands = DB::table('fashion_products')
            ->whereIn('brand_id', $purchasedBrandIds)
            ->select('category_id', DB::raw('AVG(price_b2c) as avg_price'))
            ->groupBy('category_id')
            ->get();

        $recommendations = [];

        foreach ($similarBrands as $category) {
            $brands = DB::table('fashion_products')
                ->join('fashion_brands', 'fashion_products.brand_id', '=', 'fashion_brands.id')
                ->where('fashion_products.category_id', $category->category_id)
                ->where('fashion_products.tenant_id', $tenantId)
                ->where('fashion_products.price_b2c', '>=', $category->avg_price * 0.8)
                ->where('fashion_products.price_b2c', '<=', $category->avg_price * 1.2)
                ->whereNotIn('fashion_products.brand_id', $purchasedBrandIds)
                ->select('fashion_brands.id', 'fashion_brands.name', DB::raw('COUNT(*) as product_count'))
                ->groupBy('fashion_brands.id', 'fashion_brands.name')
                ->orderByDesc('product_count')
                ->limit($limit)
                ->get()
                ->toArray();

            $recommendations = array_merge($recommendations, $brands);
        }

        return array_unique($recommendations, SORT_REGULAR);
    }
}
