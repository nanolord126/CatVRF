<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\ML\UserBehaviorAnalyzerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Сервис фильтрации товаров Fashion с ML-улучшениями.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 */
final readonly class FashionProductFilteringService
{
    private const MAX_FILTER_RESULTS = 100;
    private const ML_RECOMMENDATION_WEIGHT = 0.3;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private UserBehaviorAnalyzerService $behaviorAnalyzer,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Умная фильтрация товаров с учетом ML-рекомендаций.
     */
    public function filterProducts(
        int $userId,
        array $filters,
        ?string $sortBy = null,
        ?string $sortOrder = 'desc',
        int $page = 1,
        int $perPage = 20,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_product_filter',
            amount: 0,
            correlationId: $correlationId
        );

        $query = $this->buildBaseQuery($tenantId);
        $query = $this->applyFilters($query, $filters, $tenantId);
        $query = $this->applyMLReordering($query, $userId, $filters, $correlationId);
        $query = $this->applySorting($query, $sortBy, $sortOrder);

        $total = $query->count();
        $offset = ($page - 1) * $perPage;
        $products = $query->offset($offset)->limit(min($perPage, self::MAX_FILTER_RESULTS))->get()->toArray();

        $this->audit->record(
            action: 'fashion_products_filtered',
            subjectType: 'fashion_product_filter',
            subjectId: $userId,
            oldValues: [],
            newValues: [
                'filters' => $filters,
                'results_count' => count($products),
                'total_count' => $total,
                'page' => $page,
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion products filtered', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'filters' => $filters,
            'results_count' => count($products),
            'correlation_id' => $correlationId,
        ]);

        return [
            'products' => $this->enrichProducts($products, $userId, $correlationId),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
            'filters_applied' => $this->getAppliedFiltersSummary($filters),
            'ml_enhancements' => $this->getMLEnhancements($userId, $correlationId),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить доступные фильтры с популярными значениями.
     */
    public function getAvailableFilters(?int $userId = null, string $correlationId = ''): array
    {
        $tenantId = $this->getTenantId();
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $categories = $this->getFilterCategories($tenantId);
        $priceRanges = $this->getFilterPriceRanges($tenantId);
        $brands = $this->getFilterBrands($tenantId);
        $colors = $this->getFilterColors($tenantId);
        $sizes = $this->getFilterSizes($tenantId);
        $materials = $this->getFilterMaterials($tenantId);
        $styles = $this->getFilterStyles($tenantId);
        $seasons = $this->getFilterSeasons($tenantId);
        $targetAudiences = $this->getFilterTargetAudiences($tenantId);

        $userPreferences = $userId !== null ? $this->getUserFilterPreferences($userId, $correlationId) : [];

        return [
            'categories' => $categories,
            'price_ranges' => $priceRanges,
            'brands' => $brands,
            'colors' => $colors,
            'sizes' => $sizes,
            'materials' => $materials,
            'styles' => $styles,
            'seasons' => $seasons,
            'target_audiences' => $targetAudiences,
            'user_preferences' => $userPreferences,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Сохранить предпочтения фильтров пользователя.
     */
    public function saveUserFilterPreferences(
        int $userId,
        array $filters,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->db->table('fashion_user_filter_preferences')->updateOrInsert(
            ['user_id' => $userId, 'tenant_id' => $tenantId],
            [
                'preferred_filters' => json_encode($filters, JSON_UNESCAPED_UNICODE),
                'updated_at' => Carbon::now(),
            ]
        );

        $this->audit->record(
            action: 'fashion_user_filter_preferences_saved',
            subjectType: 'fashion_filter_preferences',
            subjectId: $userId,
            oldValues: [],
            newValues: ['filters' => $filters],
            correlationId: $correlationId
        );

        return [
            'success' => true,
            'user_id' => $userId,
            'filters_saved' => $filters,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить умные рекомендации фильтров на основе поведения.
     */
    public function getSmartFilterRecommendations(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $userBehavior = $this->behaviorAnalyzer->getPattern($userId, $this->isNewUser($userId));
        $userPreferences = $this->getUserFilterPreferences($userId, $correlationId);
        $recentViews = $this->getUserRecentViews($userId, 10);
        $recentPurchases = $this->getUserRecentPurchases($userId, 10);

        $recommendations = [
            'suggested_price_range' => $this->suggestPriceRange($userBehavior, $recentPurchases),
            'suggested_categories' => $this->suggestCategories($userBehavior, $recentViews),
            'suggested_brands' => $this->suggestBrands($recentPurchases),
            'suggested_sizes' => $this->suggestSizes($recentPurchases),
            'suggested_colors' => $this->suggestColors($recentViews),
            'suggested_styles' => $this->suggestStyles($userBehavior),
        ];

        return [
            'user_id' => $userId,
            'recommendations' => $recommendations,
            'confidence' => $this->calculateRecommendationConfidence($userBehavior, $recentViews, $recentPurchases),
            'correlation_id' => $correlationId,
        ];
    }

    private function buildBaseQuery(int $tenantId)
    {
        return $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0);
    }

    private function applyFilters($query, array $filters, int $tenantId)
    {
        if (!empty($filters['categories'])) {
            $query->whereIn('id', function ($q) use ($filters, $tenantId) {
                $q->select('product_id')
                    ->from('fashion_product_categories')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('primary_category', $filters['categories']);
            });
        }

        if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
            $priceMin = (int) ($filters['price_min'] ?? 0);
            $priceMax = (int) ($filters['price_max'] ?? PHP_INT_MAX);
            $query->whereBetween('price_b2c', [$priceMin, $priceMax]);
        }

        if (!empty($filters['brands'])) {
            $query->whereIn('brand', $filters['brands']);
        }

        if (!empty($filters['colors'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['colors'] as $color) {
                    $q->orWhere('color', 'like', "%{$color}%");
                }
            });
        }

        if (!empty($filters['sizes'])) {
            $query->whereIn('id', function ($q) use ($filters) {
                $q->select('fashion_product_id')
                    ->from('fashion_sizes')
                    ->whereIn('size_value', $filters['sizes']);
            });
        }

        if (!empty($filters['materials'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['materials'] as $material) {
                    $q->orWhere('material', 'like', "%{$material}%");
                }
            });
        }

        if (!empty($filters['styles'])) {
            $query->whereIn('id', function ($q) use ($filters, $tenantId) {
                $q->select('product_id')
                    ->from('fashion_product_categories')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('style_profile', $filters['styles']);
            });
        }

        if (!empty($filters['seasons'])) {
            $query->whereIn('id', function ($q) use ($filters, $tenantId) {
                $q->select('product_id')
                    ->from('fashion_product_categories')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('season', $filters['seasons']);
            });
        }

        if (!empty($filters['target_audiences'])) {
            $query->whereIn('id', function ($q) use ($filters, $tenantId) {
                $q->select('product_id')
                    ->from('fashion_product_categories')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('target_audience', $filters['target_audiences']);
            });
        }

        if (!empty($filters['in_stock_only'])) {
            $query->where('stock_quantity', '>', 0);
        }

        if (!empty($filters['on_sale'])) {
            $query->whereColumn('price_b2c', '<', 'old_price');
        }

        if (!empty($filters['new_arrivals'])) {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        }

        return $query;
    }

    private function applyMLReordering($query, int $userId, array $filters, string $correlationId)
    {
        $userPattern = $this->behaviorAnalyzer->getPattern($userId, $this->isNewUser($userId));
        $preferredCategories = $userPattern['preferred_categories'] ?? [];
        $preferredPriceRange = $userPattern['price_range'] ?? 'medium';

        if (!empty($preferredCategories)) {
            $query->orderByRaw(
                "FIELD((SELECT primary_category FROM fashion_product_categories WHERE product_id = fashion_products.id LIMIT 1), ?) DESC",
                [implode(',', $preferredCategories)]
            );
        }

        return $query;
    }

    private function applySorting($query, ?string $sortBy, string $sortOrder)
    {
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        return match ($sortBy) {
            'price' => $query->orderBy('price_b2c', $sortOrder),
            'name' => $query->orderBy('name', $sortOrder),
            'newest' => $query->orderBy('created_at', $sortOrder),
            'popular' => $query->orderBy('stock_quantity', $sortOrder),
            'rating' => $query->orderBy('rating', $sortOrder),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    private function enrichProducts(array $products, int $userId, string $correlationId): array
    {
        foreach ($products as &$product) {
            $product['categories'] = $this->getProductCategories((int) $product['id']);
            $product['fit_score'] = $this->calculateProductFitScore((int) $product['id'], $userId, $correlationId);
            $product['is_favorite'] = $this->isProductFavorite((int) $product['id'], $userId);
            $product['discount_percent'] = $product['old_price'] > 0
                ? round((($product['old_price'] - $product['price_b2c']) / $product['old_price']) * 100, 2)
                : 0;
        }

        return $products;
    }

    private function getProductCategories(int $productId): array
    {
        $category = $this->db->table('fashion_product_categories')
            ->where('product_id', $productId)
            ->first();

        if ($category === null) {
            return [];
        }

        return [
            'primary' => $category['primary_category'],
            'secondary' => json_decode($category['secondary_categories'] ?? '[]', true),
            'tags' => json_decode($category['tags'] ?? '[]', true),
            'style_profile' => $category['style_profile'],
            'season' => $category['season'],
            'target_audience' => $category['target_audience'],
        ];
    }

    private function calculateProductFitScore(int $productId, int $userId, string $correlationId): float
    {
        $userPattern = $this->behaviorAnalyzer->getPattern($userId, $this->isNewUser($userId));
        $productCategories = $this->getProductCategories($productId);

        $score = 0.5;

        if (in_array($productCategories['primary'], $userPattern['preferred_categories'] ?? [])) {
            $score += 0.3;
        }

        if (in_array($productCategories['style_profile'], $userPattern['preferred_styles'] ?? [])) {
            $score += 0.1;
        }

        $priceMatch = $this->checkPriceMatch($productId, $userPattern['price_range'] ?? 'medium');
        $score += $priceMatch ? 0.1 : 0;

        return min($score, 1.0);
    }

    private function checkPriceMatch(int $productId, string $priceRange): bool
    {
        $product = $this->db->table('fashion_products')->where('id', $productId)->first();
        if ($product === null) {
            return false;
        }

        $price = $product['price_b2c'];

        return match ($priceRange) {
            'budget' => $price < 1000,
            'medium' => $price >= 1000 && $price < 5000,
            'premium' => $price >= 5000,
            default => true,
        };
    }

    private function isProductFavorite(int $productId, int $userId): bool
    {
        return $this->db->table('fashion_wishlists')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    private function getAppliedFiltersSummary(array $filters): array
    {
        $summary = [];
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $summary[$key] = is_array($value) ? count($value) . ' items' : $value;
            }
        }
        return $summary;
    }

    private function getMLEnhancements(int $userId, string $correlationId): array
    {
        $userPattern = $this->behaviorAnalyzer->getPattern($userId, $this->isNewUser($userId));

        return [
            'personalized_ranking' => !empty($userPattern['preferred_categories']),
            'fit_scores' => true,
            'price_sensitivity' => $userPattern['price_sensitivity'] ?? 0.5,
        ];
    }

    private function getFilterCategories(int $tenantId): array
    {
        return $this->db->table('fashion_categories')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('parent_category')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'product_count' => $this->getCategoryProductCount($category->name),
                ];
            })
            ->toArray();
    }

    private function getFilterPriceRanges(int $tenantId): array
    {
        return [
            ['min' => 0, 'max' => 1000, 'label' => 'До 1000 ₽'],
            ['min' => 1000, 'max' => 3000, 'label' => '1000 - 3000 ₽'],
            ['min' => 3000, 'max' => 5000, 'label' => '3000 - 5000 ₽'],
            ['min' => 5000, 'max' => 10000, 'label' => '5000 - 10000 ₽'],
            ['min' => 10000, 'max' => null, 'label' => 'От 10000 ₽'],
        ];
    }

    private function getFilterBrands(int $tenantId): array
    {
        return $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->select('brand')
            ->distinct()
            ->orderBy('brand')
            ->get()
            ->pluck('brand')
            ->toArray();
    }

    private function getFilterColors(int $tenantId): array
    {
        return $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->select('color')
            ->distinct()
            ->orderBy('color')
            ->get()
            ->pluck('color')
            ->toArray();
    }

    private function getFilterSizes(int $tenantId): array
    {
        return $this->db->table('fashion_sizes')
            ->join('fashion_products', 'fashion_sizes.fashion_product_id', '=', 'fashion_products.id')
            ->where('fashion_products.tenant_id', $tenantId)
            ->select('size_value')
            ->distinct()
            ->orderBy('size_value')
            ->get()
            ->pluck('size_value')
            ->toArray();
    }

    private function getFilterMaterials(int $tenantId): array
    {
        return $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('material')
            ->select('material')
            ->distinct()
            ->orderBy('material')
            ->get()
            ->pluck('material')
            ->toArray();
    }

    private function getFilterStyles(int $tenantId): array
    {
        return $this->db->table('fashion_product_categories')
            ->where('tenant_id', $tenantId)
            ->select('style_profile')
            ->distinct()
            ->orderBy('style_profile')
            ->get()
            ->pluck('style_profile')
            ->toArray();
    }

    private function getFilterSeasons(int $tenantId): array
    {
        return [
            ['value' => 'spring', 'label' => 'Весна'],
            ['value' => 'summer', 'label' => 'Лето'],
            ['value' => 'autumn', 'label' => 'Осень'],
            ['value' => 'winter', 'label' => 'Зима'],
        ];
    }

    private function getFilterTargetAudiences(int $tenantId): array
    {
        return [
            ['value' => 'women', 'label' => 'Женщины'],
            ['value' => 'men', 'label' => 'Мужчины'],
            ['value' => 'kids', 'label' => 'Дети'],
            ['value' => 'unisex', 'label' => 'Унисекс'],
        ];
    }

    private function getUserFilterPreferences(int $userId, string $correlationId): array
    {
        $preferences = $this->db->table('fashion_user_filter_preferences')
            ->where('user_id', $userId)
            ->first();

        return $preferences !== null ? json_decode($preferences['preferred_filters'], true) : [];
    }

    private function getUserRecentViews(int $userId, int $limit): array
    {
        return $this->db->table('product_views')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->pluck('product_id')
            ->toArray();
    }

    private function getUserRecentPurchases(int $userId, int $limit): array
    {
        return $this->db->table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->orderBy('orders.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->pluck('product_id')
            ->toArray();
    }

    private function suggestPriceRange(array $userBehavior, array $recentPurchases): array
    {
        $priceRange = $userBehavior['price_range'] ?? 'medium';

        return match ($priceRange) {
            'budget' => ['min' => 0, 'max' => 2000],
            'medium' => ['min' => 1000, 'max' => 5000],
            'premium' => ['min' => 3000, 'max' => null],
            default => ['min' => 0, 'max' => null],
        };
    }

    private function suggestCategories(array $userBehavior, array $recentViews): array
    {
        return $userBehavior['preferred_categories'] ?? [];
    }

    private function suggestBrands(array $recentPurchases): array
    {
        if (empty($recentPurchases)) {
            return [];
        }

        return $this->db->table('fashion_products')
            ->whereIn('id', $recentPurchases)
            ->select('brand')
            ->distinct()
            ->get()
            ->pluck('brand')
            ->toArray();
    }

    private function suggestSizes(array $recentPurchases): array
    {
        if (empty($recentPurchases)) {
            return [];
        }

        return $this->db->table('fashion_sizes')
            ->whereIn('fashion_product_id', $recentPurchases)
            ->select('size_value')
            ->distinct()
            ->get()
            ->pluck('size_value')
            ->toArray();
    }

    private function suggestColors(array $recentViews): array
    {
        if (empty($recentViews)) {
            return [];
        }

        return $this->db->table('fashion_products')
            ->whereIn('id', $recentViews)
            ->select('color')
            ->distinct()
            ->get()
            ->pluck('color')
            ->toArray();
    }

    private function suggestStyles(array $userBehavior): array
    {
        return $userBehavior['preferred_styles'] ?? [];
    }

    private function calculateRecommendationConfidence(array $userBehavior, array $recentViews, array $recentPurchases): float
    {
        $confidence = 0.5;
        $confidence += !empty($recentViews) ? 0.2 : 0;
        $confidence += !empty($recentPurchases) ? 0.3 : 0;

        return min($confidence, 1.0);
    }

    private function getCategoryProductCount(string $categoryName): int
    {
        return $this->db->table('fashion_product_categories')
            ->where('primary_category', $categoryName)
            ->count();
    }

    private function isNewUser(int $userId): bool
    {
        $user = $this->db->table('users')->where('id', $userId)->first();
        if ($user === null) {
            return true;
        }

        $daysSinceCreation = Carbon::parse($user->created_at)->diffInDays(Carbon::now());
        $orderCount = $this->db->table('orders')->where('user_id', $userId)->count();

        return $daysSinceCreation <= 7 && $orderCount === 0;
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
