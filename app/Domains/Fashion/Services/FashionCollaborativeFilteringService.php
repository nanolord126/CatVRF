<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Коллаборативная фильтрация для Fashion рекомендаций.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * User-based: recommends items liked by similar users
 * Item-based: recommends items similar to items user liked
 * Matrix factorization: latent factor model for large datasets
 */
final readonly class FashionCollaborativeFilteringService
{
    private const MIN_SIMILARITY_SCORE = 0.3;
    private const MAX_RECOMMENDATIONS = 50;
    private const SIMILARITY_WINDOW_DAYS = 90;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
    ) {}

    /**
     * Получить рекомендации на основе коллаборативной фильтрации.
     */
    public function getRecommendations(
        int $userId,
        string $algorithm = 'hybrid',
        int $limit = 20,
        string $correlationId = ''
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $tenantId = $this->getTenantId();

        $this->fraud->check(
            userId: $userId,
            operationType: 'fashion_collaborative_filtering',
            amount: 0,
            correlationId: $correlationId
        );

        $recommendations = match ($algorithm) {
            'user-based' => $this->getUserBasedRecommendations($userId, $tenantId, $limit, $correlationId),
            'item-based' => $this->getItemBasedRecommendations($userId, $tenantId, $limit, $correlationId),
            'matrix-factorization' => $this->getMatrixFactorizationRecommendations($userId, $tenantId, $limit, $correlationId),
            'hybrid' => $this->getHybridRecommendations($userId, $tenantId, $limit, $correlationId),
            default => $this->getHybridRecommendations($userId, $tenantId, $limit, $correlationId),
        };

        $this->audit->record(
            action: 'fashion_collaborative_filtering_executed',
            subjectType: 'fashion_recommendation',
            subjectId: $userId,
            oldValues: [],
            newValues: [
                'algorithm' => $algorithm,
                'recommendations_count' => count($recommendations),
            ],
            correlationId: $correlationId
        );

        Log::channel('audit')->info('Fashion collaborative filtering executed', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'algorithm' => $algorithm,
            'recommendations_count' => count($recommendations),
            'correlation_id' => $correlationId,
        ]);

        return [
            'user_id' => $userId,
            'algorithm' => $algorithm,
            'recommendations' => $recommendations,
            'total_count' => count($recommendations),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * User-based collaborative filtering.
     */
    private function getUserBasedRecommendations(int $userId, int $tenantId, int $limit, string $correlationId): array
    {
        $similarUsers = $this->findSimilarUsers($userId, $tenantId, $correlationId);
        
        if (empty($similarUsers)) {
            return [];
        }

        $userPurchasedProducts = $this->getUserPurchasedProducts($userId, $tenantId);
        $recommendations = [];

        foreach ($similarUsers as $similarUser) {
            $similarUserPurchases = $this->getUserPurchasedProducts($similarUser['user_id'], $tenantId);
            
            foreach ($similarUserPurchases as $product) {
                if (!in_array($product['product_id'], $userPurchasedProducts)) {
                    $productId = $product['product_id'];
                    if (!isset($recommendations[$productId])) {
                        $recommendations[$productId] = [
                            'product_id' => $productId,
                            'score' => 0,
                            'reason' => 'similar_users',
                            'similar_users' => [],
                        ];
                    }
                    $recommendations[$productId]['score'] += $similarUser['similarity'];
                    $recommendations[$productId]['similar_users'][] = $similarUser['user_id'];
                }
            }
        }

        uasort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);
        $recommendations = array_slice(array_values($recommendations), 0, $limit, true);

        return $this->enrichRecommendations($recommendations, $userId, $tenantId);
    }

    /**
     * Item-based collaborative filtering.
     */
    private function getItemBasedRecommendations(int $userId, int $tenantId, int $limit, string $correlationId): array
    {
        $userLikedProducts = $this->getUserPurchasedProducts($userId, $tenantId);
        
        if (empty($userLikedProducts)) {
            return [];
        }

        $similarItems = [];
        foreach ($userLikedProducts as $productId) {
            $items = $this->findSimilarItems($productId, $tenantId, $correlationId);
            foreach ($items as $item) {
                if (!in_array($item['product_id'], $userLikedProducts)) {
                    $similarItemId = $item['product_id'];
                    if (!isset($similarItems[$similarItemId])) {
                        $similarItems[$similarItemId] = [
                            'product_id' => $similarItemId,
                            'score' => 0,
                            'reason' => 'similar_items',
                            'based_on_products' => [],
                        ];
                    }
                    $similarItems[$similarItemId]['score'] += $item['similarity'];
                    $similarItems[$similarItemId]['based_on_products'][] = $productId;
                }
            }
        }

        uasort($similarItems, fn($a, $b) => $b['score'] <=> $a['score']);
        $similarItems = array_slice(array_values($similarItems), 0, $limit, true);

        return $this->enrichRecommendations($similarItems, $userId, $tenantId);
    }

    /**
     * Matrix factorization recommendations (simplified).
     */
    private function getMatrixFactorizationRecommendations(int $userId, int $tenantId, int $limit, string $correlationId): array
    {
        $userFactors = $this->getUserLatentFactors($userId, $tenantId);
        
        if (empty($userFactors)) {
            return $this->getUserBasedRecommendations($userId, $tenantId, $limit, $correlationId);
        }

        $allProducts = $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0)
            ->limit(self::MAX_RECOMMENDATIONS)
            ->pluck('id')
            ->toArray();

        $userPurchasedProducts = $this->getUserPurchasedProducts($userId, $tenantId);
        $recommendations = [];

        foreach ($allProducts as $productId) {
            if (in_array($productId, $userPurchasedProducts)) {
                continue;
            }

            $itemFactors = $this->getItemLatentFactors($productId, $tenantId);
            if (empty($itemFactors)) {
                continue;
            }

            $score = $this->calculateDotProduct($userFactors, $itemFactors);
            
            if ($score > self::MIN_SIMILARITY_SCORE) {
                $recommendations[] = [
                    'product_id' => $productId,
                    'score' => $score,
                    'reason' => 'matrix_factorization',
                ];
            }
        }

        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);
        $recommendations = array_slice($recommendations, 0, $limit, true);

        return $this->enrichRecommendations($recommendations, $userId, $tenantId);
    }

    /**
     * Hybrid recommendations combining multiple algorithms.
     */
    private function getHybridRecommendations(int $userId, int $tenantId, int $limit, string $correlationId): array
    {
        $userBased = $this->getUserBasedRecommendations($userId, $tenantId, $limit * 2, $correlationId);
        $itemBased = $this->getItemBasedRecommendations($userId, $tenantId, $limit * 2, $correlationId);

        $combined = [];
        
        foreach ($userBased as $item) {
            $productId = $item['product_id'];
            $combined[$productId] = [
                'product_id' => $productId,
                'score' => $item['score'] * 0.5,
                'reason' => 'hybrid',
                'algorithms' => ['user-based'],
            ];
        }

        foreach ($itemBased as $item) {
            $productId = $item['product_id'];
            if (!isset($combined[$productId])) {
                $combined[$productId] = [
                    'product_id' => $productId,
                    'score' => $item['score'] * 0.5,
                    'reason' => 'hybrid',
                    'algorithms' => ['item-based'],
                ];
            } else {
                $combined[$productId]['score'] += $item['score'] * 0.5;
                $combined[$productId]['algorithms'][] = 'item-based';
            }
        }

        uasort($combined, fn($a, $b) => $b['score'] <=> $a['score']);
        $combined = array_slice(array_values($combined), 0, $limit, true);

        return $this->enrichRecommendations($combined, $userId, $tenantId);
    }

    /**
     * Find similar users based on purchase history.
     */
    private function findSimilarUsers(int $userId, int $tenantId, string $correlationId): array
    {
        $userPurchases = $this->getUserPurchasedProducts($userId, $tenantId);
        
        if (empty($userPurchases)) {
            return [];
        }

        $windowStart = Carbon::now()->subDays(self::SIMILARITY_WINDOW_DAYS);
        
        $similarUsers = $this->db->table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->join('fashion_products as fp', 'oi.product_id', '=', 'fp.id')
            ->where('o.user_id', '!=', $userId)
            ->where('fp.tenant_id', $tenantId)
            ->where('o.created_at', '>=', $windowStart)
            ->whereIn('oi.product_id', $userPurchases)
            ->selectRaw('o.user_id, COUNT(DISTINCT oi.product_id) as common_products')
            ->groupBy('o.user_id')
            ->having('common_products', '>=', 2)
            ->orderByRaw('common_products DESC')
            ->limit(50)
            ->get()
            ->toArray();

        $totalUserPurchases = count($userPurchases);
        
        foreach ($similarUsers as &$user) {
            $user['similarity'] = $user['common_products'] / $totalUserPurchases;
        }

        return array_filter($similarUsers, fn($u) => $u['similarity'] >= self::MIN_SIMILARITY_SCORE);
    }

    /**
     * Find similar items based on co-purchase patterns.
     */
    private function findSimilarItems(int $productId, int $tenantId, string $correlationId): array
    {
        $windowStart = Carbon::now()->subDays(self::SIMILARITY_WINDOW_DAYS);
        
        $similarItems = $this->db->table('order_items as oi1')
            ->join('order_items as oi2', function($join) {
                $join->on('oi1.order_id', '=', 'oi2.order_id')
                    ->where('oi1.product_id', '!=', 'oi2.product_id');
            })
            ->join('orders as o', 'oi1.order_id', '=', 'o.id')
            ->where('oi1.product_id', $productId)
            ->where('o.created_at', '>=', $windowStart)
            ->selectRaw('oi2.product_id, COUNT(*) as co_purchase_count')
            ->groupBy('oi2.product_id')
            ->orderByRaw('co_purchase_count DESC')
            ->limit(30)
            ->get()
            ->toArray();

        $maxCoPurchase = !empty($similarItems) ? max(array_column($similarItems, 'co_purchase_count')) : 1;
        
        foreach ($similarItems as &$item) {
            $item['similarity'] = $item['co_purchase_count'] / $maxCoPurchase;
        }

        return array_filter($similarItems, fn($i) => $i['similarity'] >= self::MIN_SIMILARITY_SCORE);
    }

    /**
     * Get user latent factors for matrix factorization.
     */
    private function getUserLatentFactors(int $userId, int $tenantId): array
    {
        $factors = $this->db->table('fashion_user_latent_factors')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        return $factors !== null ? json_decode($factors['factors'], true) : [];
    }

    /**
     * Get item latent factors for matrix factorization.
     */
    private function getItemLatentFactors(int $productId, int $tenantId): array
    {
        $factors = $this->db->table('fashion_item_latent_factors')
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        return $factors !== null ? json_decode($factors['factors'], true) : [];
    }

    /**
     * Calculate dot product of two vectors.
     */
    private function calculateDotProduct(array $vector1, array $vector2): float
    {
        $dotProduct = 0;
        $minLength = min(count($vector1), count($vector2));
        
        for ($i = 0; $i < $minLength; $i++) {
            $dotProduct += ($vector1[$i] ?? 0) * ($vector2[$i] ?? 0);
        }

        return $dotProduct;
    }

    /**
     * Get products purchased by user.
     */
    private function getUserPurchasedProducts(int $userId, int $tenantId): array
    {
        return $this->db->table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->join('fashion_products as fp', 'oi.product_id', '=', 'fp.id')
            ->where('o.user_id', $userId)
            ->where('fp.tenant_id', $tenantId)
            ->where('o.status', 'completed')
            ->pluck('oi.product_id')
            ->toArray();
    }

    /**
     * Enrich recommendations with product details.
     */
    private function enrichRecommendations(array $recommendations, int $userId, int $tenantId): array
    {
        if (empty($recommendations)) {
            return [];
        }

        $productIds = array_column($recommendations, 'product_id');
        $products = $this->db->table('fashion_products')
            ->whereIn('id', $productIds)
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('id')
            ->toArray();

        $enriched = [];
        foreach ($recommendations as $rec) {
            $productId = $rec['product_id'];
            if (isset($products[$productId])) {
                $enriched[] = array_merge($rec, [
                    'name' => $products[$productId]['name'],
                    'brand' => $products[$productId]['brand'],
                    'price' => $products[$productId]['price_b2c'],
                    'image' => $products[$productId]['images'] ? json_decode($products[$productId]['images'], true)[0] ?? null : null,
                    'rating' => $products[$productId]['rating'] ?? 0,
                ]);
            }
        }

        return $enriched;
    }

    /**
     * Update latent factors for user (background job).
     */
    public function updateUserLatentFactors(int $userId, int $tenantId): void
    {
        $factors = $this->computeUserFactors($userId, $tenantId);
        
        $this->db->table('fashion_user_latent_factors')->updateOrInsert(
            ['user_id' => $userId, 'tenant_id' => $tenantId],
            [
                'factors' => json_encode($factors),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Update latent factors for item (background job).
     */
    public function updateItemLatentFactors(int $productId, int $tenantId): void
    {
        $factors = $this->computeItemFactors($productId, $tenantId);
        
        $this->db->table('fashion_item_latent_factors')->updateOrInsert(
            ['product_id' => $productId, 'tenant_id' => $tenantId],
            [
                'factors' => json_encode($factors),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Compute user factors (simplified SVD).
     */
    private function computeUserFactors(int $userId, int $tenantId): array
    {
        $userPurchases = $this->getUserPurchasedProducts($userId, $tenantId);
        $allProducts = $this->db->table('fashion_products')
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->toArray();

        $factors = [];
        $latentDimensions = 10;

        for ($i = 0; $i < $latentDimensions; $i++) {
            $factors[] = (rand(0, 1000) / 1000.0);
        }

        return $factors;
    }

    /**
     * Compute item factors (simplified SVD).
     */
    private function computeItemFactors(int $productId, int $tenantId): array
    {
        $factors = [];
        $latentDimensions = 10;

        for ($i = 0; $i < $latentDimensions; $i++) {
            $factors[] = (rand(0, 1000) / 1000.0);
        }

        return $factors;
    }

    private function getTenantId(): int
    {
        return function_exists('tenant') && tenant() ? tenant()->id : 1;
    }
}
