<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Personal Shopper AI Service для Fashion.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * AI-персональный шопер: анализ предпочтений,
        рекомендации товаров, составление списков покупок,
        уведомления о скидках, стильный советник.
 */
final readonly class FashionPersonalShopperService
{
    private const MAX_RECOMMENDATIONS = 20;
    private const WISHLIST_MAX_ITEMS = 100;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Получить персональные рекомендации.
     */
    public function getPersonalRecommendations(
        int $userId,
        ?string $category = null,
        ?int $budgetMin = null,
        ?int $budgetMax = null,
        int $limit = 20,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_personal_shopper',
            amount: 0,
            correlationId: $correlationId
        );

        $userPreferences = $this->getUserPreferences($userId, $tenantId);
        $userBehavior = $this->getUserBehavior($userId, $tenantId);

        $query = $this->db->table('fashion_products as fp')
            ->where('fp.tenant_id', $tenantId)
            ->where('fp.status', 'active')
            ->where('fp.stock_quantity', '>', 0)
            ->select('fp.*');

        if ($category !== null) {
            $query->whereExists(function ($q) use ($category, $tenantId) {
                $q->select(DB::raw(1))
                    ->from('fashion_product_categories')
                    ->whereColumn('fashion_product_categories.product_id', 'fp.id')
                    ->where('fashion_product_categories.tenant_id', $tenantId)
                    ->where('primary_category', $category);
            });
        }

        if ($budgetMin !== null) {
            $query->where('fp.price_b2c', '>=', $budgetMin);
        }

        if ($budgetMax !== null) {
            $query->where('fp.price_b2c', '<=', $budgetMax);
        }

        if (!empty($userPreferences['brands'])) {
            $query->whereIn('fp.brand', $userPreferences['brands']);
        }

        if (!empty($userPreferences['colors'])) {
            $query->whereIn('fp.color', $userPreferences['colors']);
        }

        $products = $query
            ->orderBy('fp.created_at', 'desc')
            ->limit(min($limit, self::MAX_RECOMMENDATIONS))
            ->get()
            ->toArray();

        $scoredProducts = $this->scoreProducts($products, $userPreferences, $userBehavior);
        usort($scoredProducts, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'user_id' => $userId,
            'recommendations' => array_slice($scoredProducts, 0, $limit, true),
            'total_count' => count($scoredProducts),
            'preferences_used' => $userPreferences,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать список покупок.
     */
    public function createShoppingList(
        int $userId,
        string $name,
        array $productIds,
        ?string $occasion = null,
        ?int $budget = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_shopping_list_create',
            amount: 0,
            correlationId: $correlationId
        );

        foreach ($productIds as $productId) {
            $exists = $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->exists();

            if (!$exists) {
                throw new \InvalidArgumentException('Product not found', 404);
            }
        }

        $listId = $this->db->table('fashion_shopping_lists')->insertGetId([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'name' => $name,
            'occasion' => $occasion,
            'budget' => $budget,
            'status' => 'active',
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        foreach ($productIds as $productId) {
            $this->db->table('fashion_shopping_list_items')->insert([
                'list_id' => $listId,
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'is_purchased' => false,
                'correlation_id' => $correlationId,
            ]);
        }

        $this->audit->record(
            action: 'fashion_shopping_list_created',
            subjectType: 'fashion_shopping_list',
            subjectId: $listId,
            oldValues: [],
            newValues: [
                'user_id' => $userId,
                'name' => $name,
                'item_count' => count($productIds),
            ],
            correlationId: $correlationId
        );

        return [
            'list_id' => $listId,
            'user_id' => $userId,
            'name' => $name,
            'item_count' => count($productIds),
            'status' => 'active',
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Добавить в вишлист.
     */
    public function addToWishlist(
        int $userId,
        int $productId,
        ?string $note = null,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $wishlistCount = $this->db->table('fashion_wishlists')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->count();

        if ($wishlistCount >= self::WISHLIST_MAX_ITEMS) {
            throw new \RuntimeException('Wishlist capacity reached', 400);
        }

        $exists = $this->db->table('fashion_wishlists')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('Product already in wishlist', 400);
        }

        $this->db->table('fashion_wishlists')->insert([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'note' => $note,
            'price_added' => $this->getProductPrice($productId, $tenantId),
            'is_available' => true,
            'correlation_id' => $correlationId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return [
            'user_id' => $userId,
            'product_id' => $productId,
            'added' => true,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить уведомления о скидках для вишлиста.
     */
    public function getWishlistDiscountAlerts(int $userId, string $correlationId = ''): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $wishlistItems = $this->db->table('fashion_wishlists as fw')
            ->join('fashion_products as fp', 'fw.product_id', '=', 'fp.id')
            ->where('fw.user_id', $userId)
            ->where('fw.tenant_id', $tenantId)
            ->select('fw.*', 'fp.price_b2c', 'fp.name', 'fp.brand')
            ->get()
            ->toArray();

        $discountedItems = [];
        foreach ($wishlistItems as $item) {
            if ($item['price_b2c'] < $item['price_added']) {
                $discountedItems[] = [
                    'wishlist_item_id' => $item['id'],
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'brand' => $item['brand'],
                    'original_price' => $item['price_added'],
                    'current_price' => $item['price_b2c'],
                    'discount' => round((($item['price_added'] - $item['price_b2c']) / $item['price_added']) * 100, 2),
                ];
            }
        }

        return [
            'user_id' => $userId,
            'discounted_items' => $discountedItems,
            'total_count' => count($discountedItems),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить стильный совет.
     */
    public function getStyleAdvice(
        int $userId,
        string $query,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $userPreferences = $this->getUserPreferences($userId, $tenantId);
        $advice = $this->generateStyleAdvice($query, $userPreferences);

        return [
            'user_id' => $userId,
            'query' => $query,
            'advice' => $advice,
            'correlation_id' => $correlationId,
        ];
    }

    private function getUserPreferences(int $userId, int $tenantId): array
    {
        $patterns = $this->db->table('fashion_user_memory_patterns')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('pattern_type')
            ->toArray();

        return [
            'brands' => $patterns['preferred_brands']['pattern_value'] ?? [],
            'colors' => $patterns['preferred_colors']['pattern_value'] ?? [],
            'categories' => $patterns['preferred_categories']['pattern_value'] ?? [],
            'price_range' => $patterns['price_range']['pattern_value'] ?? null,
        ];
    }

    private function getUserBehavior(int $userId, int $tenantId): array
    {
        $interactions = $this->db->table('fashion_user_memory_interactions')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->selectRaw('interaction_type, COUNT(*) as count')
            ->groupBy('interaction_type')
            ->get()
            ->keyBy('interaction_type')
            ->toArray();

        return [
            'total_interactions' => array_sum(array_column($interactions, 'count')),
            'interaction_types' => $interactions,
        ];
    }

    private function scoreProducts(array $products, array $preferences, array $behavior): array
    {
        foreach ($products as &$product) {
            $score = 0.5;

            if (in_array($product['brand'], $preferences['brands'] ?? [])) {
                $score += 0.2;
            }

            if (in_array(strtolower($product['color']), array_map('strtolower', $preferences['colors'] ?? []))) {
                $score += 0.15;
            }

            if ($behavior['total_interactions'] > 0) {
                $score += 0.1;
            }

            $product['score'] = min($score, 1.0);
        }

        return $products;
    }

    private function getProductPrice(int $productId, int $tenantId): float
    {
        $product = $this->db->table('fashion_products')
            ->where('id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        return $product ? (float) $product['price_b2c'] : 0.0;
    }

    private function generateStyleAdvice(string $query, array $preferences): array
    {
        $advice = [];

        $queryLower = strtolower($query);

        if (str_contains($queryLower, 'color')) {
            $colors = $preferences['colors'] ?? [];
            if (!empty($colors)) {
                $advice[] = sprintf('Based on your preferences, consider colors: %s', implode(', ', $colors));
            } else {
                $advice[] = 'Start with neutral colors (black, white, gray) as a base';
            }
        }

        if (str_contains($queryLower, 'style')) {
            $categories = $preferences['categories'] ?? [];
            if (!empty($categories)) {
                $advice[] = sprintf('Your preferred styles include: %s', implode(', ', $categories));
            } else {
                $advice[] = 'Start with classic pieces that never go out of style';
            }
        }

        if (str_contains($queryLower, 'budget')) {
            $priceRange = $preferences['price_range'] ?? null;
            if ($priceRange) {
                $advice[] = sprintf('Your preferred price range is: %s', $priceRange);
            } else {
                $advice[] = 'Invest in quality basics that can be mixed and matched';
            }
        }

        if (empty($advice)) {
            $advice[] = 'Focus on building a versatile wardrobe with pieces that work for multiple occasions';
            $advice[] = 'Consider the 80/20 rule: 80% basics, 20% statement pieces';
        }

        return $advice;
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
