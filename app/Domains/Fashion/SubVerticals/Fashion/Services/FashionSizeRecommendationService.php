<?php

declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\LogManager;
use Carbon\Carbon;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class FashionSizeRecommendationService
{
    use WithAuditLogging;

    private const CACHE_TTL = 86400;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Repository $cache,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get size recommendation for a user based on their measurements and purchase history
     */
    public function getSizeRecommendation(int $userId, int $tenantId, int $productId): array
    {
        $cacheKey = "fashion_size_recommendation:{$tenantId}:{$userId}:{$productId}";

        return $this->cache->remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($userId, $tenantId, $productId) {
            $product = $this->db->table('fashion_products')
                ->where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $product) {
                return ['error' => 'Product not found'];
            }

            // Get user's body measurements
            $userMeasurements = $this->db->table('fashion_user_size_profiles')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->first();

            // Get user's purchase history for this brand/category
            $purchaseHistory = $this->getUserPurchaseHistory($userId, $tenantId, $product);

            // Get product size chart
            $sizeChart = $this->getProductSizeChart($productId, $tenantId);

            // Calculate recommended size
            $recommendedSize = $this->calculateRecommendedSize(
                $userMeasurements,
                $purchaseHistory,
                $sizeChart,
                $product
            );

            return [
                'recommended_size' => $recommendedSize,
                'confidence' => $this->calculateConfidence($userMeasurements, $purchaseHistory),
                'size_chart' => $sizeChart,
                'fit_tips' => $this->getFitTips($product->category_id),
            ];
        });
    }

    /**
     * Save user measurements for future recommendations
     */
    public function saveUserMeasurements(int $userId, int $tenantId, array $measurements): bool
    {
        try {
            $this->db->table('fashion_user_size_profiles')->updateOrInsert(
                ['user_id' => $userId, 'tenant_id' => $tenantId],
                [
                    'chest' => $measurements['chest'] ?? null,
                    'waist' => $measurements['waist'] ?? null,
                    'hips' => $measurements['hips'] ?? null,
                    'height' => $measurements['height'] ?? null,
                    'weight' => $measurements['weight'] ?? null,
                    'shoe_size' => $measurements['shoe_size'] ?? null,
                    'updated_at' => Carbon::now(),
                ]
            );

            // Clear size recommendation cache for this user
            $this->cache->tags(["fashion_size_recommendations:{$tenantId}:{$userId}"])->flush();

            $this->log->info('User measurements saved', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->log->error('Failed to save user measurements', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user's purchase history for size analysis
     */
    private function getUserPurchaseHistory(int $userId, int $tenantId, object $product): array
    {
        return $this->db->table('fashion_order_items')
            ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
            ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
            ->join('fashion_product_categories', 'fashion_products.id', '=', 'fashion_product_categories.fashion_product_id')
            ->where('fashion_orders.user_id', $userId)
            ->where('fashion_orders.tenant_id', $tenantId)
            ->where('fashion_orders.status', 'completed')
            ->where('fashion_product_categories.fashion_category_id', $product->category_id)
            ->select('fashion_products.size', 'fashion_products.brand_id', $this->db->raw('COUNT(*) as purchase_count'))
            ->groupBy('fashion_products.size', 'fashion_products.brand_id')
            ->orderByDesc('purchase_count')
            ->get()
            ->toArray();
    }

    /**
     * Get product size chart
     */
    private function getProductSizeChart(int $productId, int $tenantId): array
    {
        $product = $this->db->table('fashion_products')
            ->where('id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $product) {
            return [];
        }

        return $this->db->table('fashion_sizes')
            ->where('brand_id', $product->brand_id)
            ->where('category_id', $product->category_id)
            ->where('gender', $product->gender)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Calculate recommended size based on measurements and history
     */
    private function calculateRecommendedSize(?object $measurements, array $purchaseHistory, array $sizeChart, object $product): ?string
    {
        // If user has purchase history, use that as primary signal
        if (! empty($purchaseHistory)) {
            $mostPurchasedSize = $purchaseHistory[0]['size'];

            return $mostPurchasedSize;
        }

        // If user has measurements, calculate based on size chart
        if ($measurements && ! empty($sizeChart)) {
            return $this->findBestFitFromMeasurements($measurements, $sizeChart);
        }

        // Default to medium/average size
        return $this->getDefaultSize($product->gender);
    }

    /**
     * Find best fit from measurements
     */
    private function findBestFitFromMeasurements(object $measurements, array $sizeChart): ?string
    {
        $bestFit = null;
        $bestScore = PHP_FLOAT_MAX;

        foreach ($sizeChart as $size) {
            $score = 0;

            // Compare chest/bust
            if ($measurements->chest && $size->chest) {
                $score += abs($measurements->chest - $size->chest);
            }

            // Compare waist
            if ($measurements->waist && $size->waist) {
                $score += abs($measurements->waist - $size->waist);
            }

            // Compare hips
            if ($measurements->hips && $size->hips) {
                $score += abs($measurements->hips - $size->hips);
            }

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestFit = $size->size;
            }
        }

        return $bestFit;
    }

    /**
     * Get default size based on gender
     */
    private function getDefaultSize(?string $gender): string
    {
        return match($gender) {
            'male' => 'M',
            'female' => 'M',
            default => 'M',
        };
    }

    /**
     * Calculate confidence level for recommendation
     */
    private function calculateConfidence(?object $measurements, array $purchaseHistory): float
    {
        $confidence = 0.0;

        if (! empty($purchaseHistory)) {
            $confidence += 0.7;
        }

        if ($measurements) {
            $confidence += 0.3;
        }

        return min($confidence, 1.0);
    }

    /**
     * Get fit tips for specific category
     */
    private function getFitTips(?int $categoryId): array
    {
        $tips = [
            'Jeans fit better when they\'re slightly tight at the waist',
            'Shirts should allow 2-3 cm of ease around the chest',
            'Dresses should fit snugly at the waist but flow at the hips',
            'Shoes typically need 1-2 cm of space at the toe',
        ];

        return $tips;
    }
}
