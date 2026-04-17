<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class FashionSearchService
{
    private const CACHE_TTL = 1800;

    /**
     * Search products with filters
     */
    public function searchProducts(array $filters, int $tenantId, int $page = 1, int $perPage = 20): array
    {
        $cacheKey = "fashion_search:{$tenantId}:" . md5(json_encode($filters) . $page . $perPage);

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($filters, $tenantId, $page, $perPage) {
            $query = DB::table('fashion_products')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('available_stock', '>', 0);

            // Apply text search
            if (!empty($filters['query'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['query'] . '%')
                      ->orWhere('description', 'like', '%' . $filters['query'] . '%')
                      ->orWhere('brand', 'like', '%' . $filters['query'] . '%');
                });
            }

            // Apply category filter
            if (!empty($filters['category_id'])) {
                $query->join('fashion_product_categories', 'fashion_products.id', '=', 'fashion_product_categories.fashion_product_id')
                      ->where('fashion_product_categories.fashion_category_id', $filters['category_id']);
            }

            // Apply brand filter
            if (!empty($filters['brand_id'])) {
                $query->where('brand_id', $filters['brand_id']);
            }

            // Apply price range filter
            if (!empty($filters['min_price'])) {
                $query->where('price_b2c', '>=', $filters['min_price']);
            }
            if (!empty($filters['max_price'])) {
                $query->where('price_b2c', '<=', $filters['max_price']);
            }

            // Apply size filter
            if (!empty($filters['size'])) {
                $query->where('size', $filters['size']);
            }

            // Apply color filter
            if (!empty($filters['color'])) {
                $query->where('color', $filters['color']);
            }

            // Apply gender filter
            if (!empty($filters['gender'])) {
                $query->where('gender', $filters['gender']);
            }

            // Apply discount filter
            if (!empty($filters['has_discount'])) {
                $query->whereNotNull('discount_percent');
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'relevance';
            $sortOrder = $filters['sort_order'] ?? 'desc';

            match($sortBy) {
                'price' => $query->orderBy('price_b2c', $sortOrder),
                'name' => $query->orderBy('name', $sortOrder),
                'created' => $query->orderBy('created_at', $sortOrder),
                'rating' => $query->orderBy('rating', $sortOrder),
                'popularity' => $query->orderBy('views', $sortOrder),
                default => $query->orderBy('id', 'desc'),
            };

            // Get total count
            $total = $query->count();

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $products = $query->offset($offset)->limit($perPage)->get()->toArray();

            return [
                'products' => $products,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                ],
            ];
        });
    }

    /**
     * Get search suggestions (autocomplete)
     */
    public function getSearchSuggestions(string $query, int $tenantId, int $limit = 10): array
    {
        $cacheKey = "fashion_search_suggestions:{$tenantId}:" . md5($query);

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($query, $tenantId, $limit) {
            return DB::table('fashion_products')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('name', 'like', $query . '%')
                ->select('id', 'name', 'image_url')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Get popular search terms
     */
    public function getPopularSearchTerms(int $tenantId, int $limit = 10): array
    {
        $cacheKey = "fashion_popular_searches:{$tenantId}";

        return Cache::remember($cacheKey, Carbon::now()->addHours(6), function () use ($tenantId, $limit) {
            return DB::table('fashion_search_logs')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->select('query', DB::raw('COUNT(*) as search_count'))
                ->groupBy('query')
                ->orderByDesc('search_count')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Log search query
     */
    public function logSearch(string $query, int $userId, int $tenantId, int $resultsCount): void
    {
        try {
            DB::table('fashion_search_logs')->insert([
                'query' => $query,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'results_count' => $resultsCount,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log search', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get search filters options
     */
    public function getSearchFilters(int $tenantId): array
    {
        $cacheKey = "fashion_search_filters:{$tenantId}";

        return Cache::remember($cacheKey, Carbon::now()->addHours(24), function () use ($tenantId) {
            return [
                'categories' => DB::table('fashion_categories')
                    ->where('tenant_id', $tenantId)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get()
                    ->toArray(),
                'brands' => DB::table('fashion_products')
                    ->where('tenant_id', $tenantId)
                    ->whereNotNull('brand_id')
                    ->select('brand_id as id', 'brand as name')
                    ->distinct()
                    ->orderBy('brand')
                    ->get()
                    ->toArray(),
                'sizes' => DB::table('fashion_products')
                    ->where('tenant_id', $tenantId)
                    ->select('size')
                    ->distinct()
                    ->orderBy('size')
                    ->get()
                    ->pluck('size')
                    ->toArray(),
                'colors' => DB::table('fashion_products')
                    ->where('tenant_id', $tenantId)
                    ->whereNotNull('color')
                    ->select('color')
                    ->distinct()
                    ->orderBy('color')
                    ->get()
                    ->pluck('color')
                    ->toArray(),
                'price_range' => [
                    'min' => DB::table('fashion_products')
                        ->where('tenant_id', $tenantId)
                        ->min('price_b2c') ?? 0,
                    'max' => DB::table('fashion_products')
                        ->where('tenant_id', $tenantId)
                        ->max('price_b2c') ?? 0,
                ],
            ];
        });
    }

    /**
     * Visual search by image
     */
    public function visualSearch(string $imageUrl, int $tenantId, int $limit = 10): array
    {
        try {
            // This would typically use an AI service to find similar products
            // For now, return trending products as placeholder
            $products = DB::table('fashion_products')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('available_stock', '>', 0)
                ->select('id', 'name', 'image_url', 'price_b2c')
                ->orderByDesc('views')
                ->limit($limit)
                ->get()
                ->toArray();

            Log::info('Visual search performed', [
                'image_url' => $imageUrl,
                'tenant_id' => $tenantId,
                'results_count' => count($products),
            ]);

            return $products;
        } catch (\Exception $e) {
            Log::error('Visual search failed', [
                'image_url' => $imageUrl,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get similar products
     */
    public function getSimilarProducts(int $productId, int $tenantId, int $limit = 6): array
    {
        $cacheKey = "fashion_similar:{$tenantId}:{$productId}";

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($productId, $tenantId, $limit) {
            $product = DB::table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$product) {
                return [];
            }

            return DB::table('fashion_products')
                ->join('fashion_product_categories', 'fashion_products.id', '=', 'fashion_product_categories.fashion_product_id')
                ->where('fashion_products.tenant_id', $tenantId)
                ->where('fashion_products.id', '!=', $productId)
                ->where('fashion_products.status', 'active')
                ->where('fashion_products.available_stock', '>', 0)
                ->where('fashion_product_categories.fashion_category_id', function ($query) use ($product) {
                    $query->whereIn('fashion_product_categories.fashion_category_id', function ($q) use ($product) {
                        $q->select('fashion_category_id')
                          ->from('fashion_product_categories')
                          ->where('fashion_product_id', $product->id);
                    });
                })
                ->select('fashion_products.id', 'fashion_products.name', 'fashion_products.image_url', 'fashion_products.price_b2c')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }
}
