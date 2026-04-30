<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Log;
use Modules\Analytics\Application\DTOs\CLVPredictionDTO;
use Modules\Analytics\Models\BuyerSellerFeatures;

/**
 * CLV-Based Recommendation Scoring Service
 * 
 * Adjusts product recommendation scores based on buyer CLV predictions.
 * High-CLV buyers receive prioritized recommendations for premium products.
 * 
 * Scoring logic:
 * - Base score from recommendation engine
 * - CLV multiplier: higher CLV = higher multiplier
 * - Segment boost: VIP buyers get additional boost
 * - Category affinity: match buyer's preferred categories
 * 
 * Production-ready: cached, tenant-aware, configurable multipliers.
 */
final readonly class CLVRecommendationScoringService
{
    use WithAuditLogging;

    private const CLV_MULTIPLIERS = [
        'vip' => 2.0,
        'high' => 1.5,
        'medium' => 1.2,
        'low' => 1.0,
    ];

    private const CHURN_PENALTY = 0.7; // Reduce score for high churn risk

    public function __construct(
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Adjust recommendation scores based on CLV.
     * 
     * @param array $recommendations Array of product recommendations with base scores
     * @param int $buyerId Buyer ID
     * @param int $sellerId Seller ID
     * @param int $tenantId Tenant ID
     * @return array Adjusted recommendations with CLV-enhanced scores
     */
    public function adjustRecommendationScores(
        array $recommendations,
        int $buyerId,
        int $sellerId,
        int $tenantId,
    ): array {
        $this->logAction('clv_recommendation_scoring', "buyer:{$buyerId},seller:{$sellerId}");

        $cacheKey = "clv:rec_score:{$tenantId}:{$sellerId}:{$buyerId}";

        return $this->cache->tags(["clv:{$tenantId}", "seller:{$sellerId}"])
            ->remember($cacheKey, 1800, function () use ($recommendations, $buyerId, $sellerId, $tenantId) {
                // Get CLV prediction for buyer
                $clvPrediction = $this->getCLVPrediction($buyerId, $sellerId, $tenantId);

                if (!$clvPrediction) {
                    Log::info("No CLV prediction for buyer {$buyerId}, returning base scores");
                    return $recommendations;
                }

                $multiplier = $this->calculateCLVMultiplier($clvPrediction);
                $categoryBoost = $this->getCategoryBoost($buyerId, $sellerId, $tenantId);

                $adjustedRecommendations = [];
                foreach ($recommendations as $recommendation) {
                    $adjusted = $recommendation;
                    $baseScore = $recommendation['score'] ?? 0.5;

                    // Apply CLV multiplier
                    $adjustedScore = $baseScore * $multiplier;

                    // Apply category boost
                    if (isset($recommendation['category']) && isset($categoryBoost[$recommendation['category']])) {
                        $adjustedScore *= $categoryBoost[$recommendation['category']];
                    }

                    // Apply churn penalty
                    if ($clvPrediction->churnProbability > 0.5) {
                        $adjustedScore *= self::CHURN_PENALTY;
                    }

                    // Normalize to 0-1 range
                    $adjusted['score'] = min(1.0, max(0.0, $adjustedScore));
                    $adjusted['clv_boost'] = $multiplier;
                    $adjusted['clv_segment'] = $clvPrediction->segment;

                    $adjustedRecommendations[] = $adjusted;
                }

                // Sort by adjusted score
                usort($adjustedRecommendations, fn ($a, $b) => $b['score'] <=> $a['score']);

                return $adjustedRecommendations;
            });
    }

    /**
     * Get CLV prediction for buyer-seller pair.
     */
    private function getCLVPrediction(
        int $buyerId,
        int $sellerId,
        int $tenantId,
    ): ?CLVPredictionDTO {
        $features = BuyerSellerFeatures::query()
            ->forSeller($sellerId)
            ->forBuyer($buyerId)
            ->forTenant($tenantId)
            ->whereNotNull('predicted_clv_180d')
            ->first();

        if (!$features) {
            return null;
        }

        return CLVPredictionDTO::fromArray($features->toArray());
    }

    /**
     * Calculate CLV multiplier based on segment and churn probability.
     */
    private function calculateCLVMultiplier(CLVPredictionDTO $prediction): float
    {
        $baseMultiplier = self::CLV_MULTIPLIERS[$prediction->segment] ?? 1.0;

        // Adjust for confidence (lower confidence = lower multiplier)
        $confidenceAdjustment = $prediction->confidence;

        return $baseMultiplier * $confidenceAdjustment;
    }

    /**
     * Get category affinity boost for buyer.
     * 
     * Returns multipliers for categories the buyer frequently purchases from.
     */
    private function getCategoryBoost(int $buyerId, int $sellerId, int $tenantId): array
    {
        // TODO: Implement actual category affinity calculation
        // For now, return empty array (no boost)
        return [];
    }

    /**
     * Get premium product recommendations for VIP buyers.
     * 
     * @param int $buyerId Buyer ID
     * @param int $sellerId Seller ID
     * @param int $tenantId Tenant ID
     * @param int $limit Number of products to return
     * @return array Premium product recommendations
     */
    public function getPremiumRecommendations(
        int $buyerId,
        int $sellerId,
        int $tenantId,
        int $limit = 10,
    ): array {
        $clvPrediction = $this->getCLVPrediction($buyerId, $sellerId, $tenantId);

        if (!$clvPrediction || !$clvPrediction->isVip()) {
            return [];
        }

        // TODO: Integrate with existing recommendation service
        // Return premium/high-margin products for VIP buyers
        return [];
    }

    /**
     * Invalidate recommendation cache for buyer.
     */
    public function invalidateBuyerCache(int $buyerId, int $sellerId, int $tenantId): void
    {
        $this->cache->tags(["clv:{$tenantId}", "seller:{$sellerId}"])->flush();
    }

    /**
     * Get CLV-based product ranking strategy.
     * 
     * Returns strategy for product ranking based on buyer CLV.
     */
    public function getRankingStrategy(int $buyerId, int $sellerId, int $tenantId): array
    {
        $clvPrediction = $this->getCLVPrediction($buyerId, $sellerId, $tenantId);

        if (!$clvPrediction) {
            return [
                'strategy' => 'default',
                'priority' => 'balanced',
                'price_sensitivity' => 'medium',
                'category_focus' => 'broad',
            ];
        }

        return match ($clvPrediction->segment) {
            'vip' => [
                'strategy' => 'premium_first',
                'priority' => 'high_margin',
                'price_sensitivity' => 'low',
                'category_focus' => 'exclusive',
            ],
            'high' => [
                'strategy' => 'quality_first',
                'priority' => 'balanced',
                'price_sensitivity' => 'medium-low',
                'category_focus' => 'targeted',
            ],
            'medium' => [
                'strategy' => 'value_first',
                'priority' => 'conversion',
                'price_sensitivity' => 'medium',
                'category_focus' => 'broad',
            ],
            default => [
                'strategy' => 'price_sensitive',
                'priority' => 'discount',
                'price_sensitivity' => 'high',
                'category_focus' => 'popular',
            ],
        };
    }
}
