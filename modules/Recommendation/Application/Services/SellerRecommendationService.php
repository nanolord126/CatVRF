<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Entities\SellerRecommendation;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;
use Modules\Recommendation\Domain\ValueObjects\FeatureVector;

final readonly class SellerRecommendationService
{
    use WithAuditLogging;

    public function __construct(
        private RecommendationRepositoryInterface $repository,
        private AuditService $audit,
    ) {}

    public function suggestProductsToPromote(int $tenantId, int $sellerId, int $limit = 12): array
    {
        $this->logAction('seller_recommendation_request', 'SellerRecommendation', null, [
            'seller_id' => $sellerId,
            'tenant_id' => $tenantId,
        ], $sellerId, $tenantId);

        try {
            $sellerFeatures = $this->repository->getSellerFeatures($tenantId, $sellerId);
            $recommendations = $this->generateSellerRecommendations($tenantId, $sellerId, $sellerFeatures, $limit);

            $this->logAction('seller_recommendation_generated', 'SellerRecommendation', null, [
                'seller_id' => $sellerId,
                'count' => count($recommendations),
            ], $sellerId, $tenantId);

            return array_map(fn(SellerRecommendation $rec) => $rec->toArray(), $recommendations);
        } catch (\Throwable $e) {
            Log::error('Seller recommendation generation failed', [
                'seller_id' => $sellerId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackSellerRecommendations($tenantId, $sellerId, $limit);
        }
    }

    private function generateSellerRecommendations(int $tenantId, int $sellerId, FeatureVector $sellerFeatures, int $limit): array
    {
        $recommendations = [];

        $trendingItems = $this->repository->getTrendingItems($tenantId, 50);
        $sellerItems = array_filter($trendingItems, fn($item) => $item['seller_id'] === $sellerId);

        $inventoryOpportunities = $this->analyzeInventoryOpportunities($tenantId, $sellerId, $sellerItems);
        foreach ($inventoryOpportunities as $opp) {
            $recommendations[] = new SellerRecommendation(
                sellerId: $sellerId,
                tenantId: $tenantId,
                productId: $opp['product_id'],
                predictedUplift: $opp['uplift'],
                confidence: $opp['confidence'],
                reason: $opp['reason'],
                supportingData: $opp['data'],
            );
        }

        $pricingOpportunities = $this->analyzePricingOpportunities($tenantId, $sellerId, $sellerItems);
        foreach ($pricingOpportunities as $opp) {
            $recommendations[] = new SellerRecommendation(
                sellerId: $sellerId,
                tenantId: $tenantId,
                productId: $opp['product_id'],
                predictedUplift: $opp['uplift'],
                confidence: $opp['confidence'],
                reason: $opp['reason'],
                supportingData: $opp['data'],
            );
        }

        $crossSellOpportunities = $this->analyzeCrossSellOpportunities($tenantId, $sellerId, $sellerItems);
        foreach ($crossSellOpportunities as $opp) {
            $recommendations[] = new SellerRecommendation(
                sellerId: $sellerId,
                tenantId: $tenantId,
                productId: $opp['product_id'],
                predictedUplift: $opp['uplift'],
                confidence: $opp['confidence'],
                reason: $opp['reason'],
                supportingData: $opp['data'],
            );
        }

        $seasonalOpportunities = $this->analyzeSeasonalOpportunities($tenantId, $sellerId);
        foreach ($seasonalOpportunities as $opp) {
            $recommendations[] = new SellerRecommendation(
                sellerId: $sellerId,
                tenantId: $tenantId,
                productId: $opp['product_id'],
                predictedUplift: $opp['uplift'],
                confidence: $opp['confidence'],
                reason: $opp['reason'],
                supportingData: $opp['data'],
            );
        }

        usort($recommendations, fn($a, $b) => $b->getPredictedUplift() <=> $a->getPredictedUplift());

        return array_slice($recommendations, 0, $limit);
    }

    private function analyzeInventoryOpportunities(int $tenantId, int $sellerId, array $sellerItems): array
    {
        $opportunities = [];

        foreach ($sellerItems as $item) {
            $currentScore = $item['score'] ?? 0.5;
            $position = $item['position'] ?? 50;

            if ($currentScore > 0.6 && $position > 20) {
                $potentialUplift = min(0.25, (1.0 - $currentScore) * 0.5);

                $opportunities[] = [
                    'product_id' => $item['item_id'],
                    'uplift' => $potentialUplift,
                    'confidence' => 0.7,
                    'reason' => 'Product has high quality but low visibility. Promoting to top positions could increase sales.',
                    'data' => [
                        'current_score' => $currentScore,
                        'current_position' => $position,
                        'opportunity_type' => 'visibility',
                    ],
                ];
            }

            if ($currentScore < 0.4 && $item['clicks'] > 10) {
                $opportunities[] = [
                    'product_id' => $item['item_id'],
                    'uplift' => 0.15,
                    'confidence' => 0.6,
                    'reason' => 'Product has engagement but low conversion. Consider price optimization or improved images.',
                    'data' => [
                        'current_score' => $currentScore,
                        'clicks' => $item['clicks'] ?? 0,
                        'opportunity_type' => 'conversion',
                    ],
                ];
            }
        }

        return $opportunities;
    }

    private function analyzePricingOpportunities(int $tenantId, int $sellerId, array $sellerItems): array
    {
        $opportunities = [];

        foreach ($sellerItems as $item) {
            if (isset($item['price']) && isset($item['competitor_avg_price'])) {
                $priceRatio = $item['price'] / $item['competitor_avg_price'];

                if ($priceRatio > 1.2) {
                    $priceDiscountUplift = min(0.3, ($priceRatio - 1.0) * 0.5);

                    $opportunities[] = [
                        'product_id' => $item['item_id'],
                        'uplift' => $priceDiscountUplift,
                        'confidence' => 0.75,
                        'reason' => sprintf(
                            'Price is %.0f%% above market average. A 5-10%% discount could significantly boost sales.',
                            ($priceRatio - 1.0) * 100
                        ),
                        'data' => [
                            'current_price' => $item['price'],
                            'competitor_avg_price' => $item['competitor_avg_price'],
                            'price_ratio' => $priceRatio,
                            'opportunity_type' => 'pricing',
                        ],
                    ];
                }

                if ($priceRatio < 0.85 && $item['stock'] > 50) {
                    $opportunities[] = [
                        'product_id' => $item['item_id'],
                        'uplift' => 0.1,
                        'confidence' => 0.65,
                        'reason' => 'Price is below market. Consider increasing price to improve margins while staying competitive.',
                        'data' => [
                            'current_price' => $item['price'],
                            'competitor_avg_price' => $item['competitor_avg_price'],
                            'price_ratio' => $priceRatio,
                            'stock' => $item['stock'],
                            'opportunity_type' => 'margin',
                        ],
                    ];
                }
            }
        }

        return $opportunities;
    }

    private function analyzeCrossSellOpportunities(int $tenantId, int $sellerId, array $sellerItems): array
    {
        $opportunities = [];

        $topItems = array_slice($sellerItems, 0, 5);
        $lowerItems = array_slice($sellerItems, 5);

        foreach ($lowerItems as $lowerItem) {
            foreach ($topItems as $topItem) {
                $similarity = $this->calculateItemSimilarity($topItem, $lowerItem);

                if ($similarity > 0.7) {
                    $opportunities[] = [
                        'product_id' => $lowerItem['item_id'],
                        'uplift' => 0.12,
                        'confidence' => 0.7,
                        'reason' => sprintf(
                            'Similar to top-selling product "%s". Bundle promotion could increase visibility.',
                            $topItem['name'] ?? 'Unknown'
                        ),
                        'data' => [
                            'similar_top_product' => $topItem['item_id'],
                            'similarity' => $similarity,
                            'opportunity_type' => 'cross_sell',
                        ],
                    ];

                    break;
                }
            }
        }

        return $opportunities;
    }

    private function analyzeSeasonalOpportunities(int $tenantId, int $sellerId): array
    {
        $opportunities = [];
        $currentMonth = (int) date('m');

        $seasonalCategories = [
            12 => ['gifts', 'decorations', 'toys'],
            1 => ['fitness', 'organization', 'wellness'],
            6 => ['outdoor', 'swimwear', 'travel'],
            7 => ['vacation', 'summer', 'outdoor'],
            11 => ['winter', 'coats', 'boots'],
        ];

        if (isset($seasonalCategories[$currentMonth])) {
            $opportunities[] = [
                'product_id' => 0,
                'uplift' => 0.08,
                'confidence' => 0.6,
                'reason' => sprintf(
                    'Seasonal opportunity for categories: %s. Consider promoting relevant products.',
                    implode(', ', $seasonalCategories[$currentMonth])
                ),
                'data' => [
                    'season' => $currentMonth,
                    'categories' => $seasonalCategories[$currentMonth],
                    'opportunity_type' => 'seasonal',
                ],
            ];
        }

        return $opportunities;
    }

    private function calculateItemSimilarity(array $item1, array $item2): float
    {
        $similarity = 0.0;
        $factors = 0;

        if (isset($item1['category_id']) && isset($item2['category_id'])) {
            if ($item1['category_id'] === $item2['category_id']) {
                $similarity += 0.4;
            }
            $factors++;
        }

        if (isset($item1['price']) && isset($item2['price'])) {
            $priceDiff = abs($item1['price'] - $item2['price']) / max($item1['price'], $item2['price']);
            $similarity += max(0, 0.3 - $priceDiff);
            $factors++;
        }

        if (isset($item1['tags']) && isset($item2['tags'])) {
            $tags1 = is_array($item1['tags']) ? $item1['tags'] : explode(',', $item1['tags']);
            $tags2 = is_array($item2['tags']) ? $item2['tags'] : explode(',', $item2['tags']);
            $commonTags = array_intersect($tags1, $tags2);
            $similarity += min(0.3, count($commonTags) * 0.1);
            $factors++;
        }

        return $factors > 0 ? $similarity / $factors : 0.0;
    }

    private function getFallbackSellerRecommendations(int $tenantId, int $sellerId, int $limit): array
    {
        $recommendations = [];

        $trendingItems = $this->repository->getTrendingItems($tenantId, 30);
        $sellerItems = array_filter($trendingItems, fn($item) => $item['seller_id'] === $sellerId);

        $sortedItems = array_slice($sellerItems, 0, $limit);

        foreach ($sortedItems as $idx => $item) {
            $recommendations[] = new SellerRecommendation(
                sellerId: $sellerId,
                tenantId: $tenantId,
                productId: $item['item_id'],
                predictedUplift: 0.05,
                confidence: 0.5,
                reason: 'Trending product from your catalog',
                supportingData: [
                    'current_score' => $item['score'] ?? 0.5,
                    'position' => $item['position'] ?? 0,
                ],
            );
        }

        return $recommendations;
    }

    public function getSellerPerformanceMetrics(int $tenantId, int $sellerId): array
    {
        $sellerFeatures = $this->repository->getSellerFeatures($tenantId, $sellerId);

        return [
            'seller_id' => $sellerId,
            'tenant_id' => $tenantId,
            'overall_score' => $sellerFeatures->getValues()[0] ?? 0.5,
            'visibility_score' => $sellerFeatures->getValues()[1] ?? 0.5,
            'conversion_score' => $sellerFeatures->getValues()[2] ?? 0.5,
            'customer_satisfaction' => $sellerFeatures->getValues()[3] ?? 0.5,
            'inventory_health' => $sellerFeatures->getValues()[4] ?? 0.5,
            'recommendations_count' => $this->getRecommendationCount($tenantId, $sellerId),
            'last_updated' => now()->toIso8601String(),
        ];
    }

    private function getRecommendationCount(int $tenantId, int $sellerId): int
    {
        return 0;
    }
}
