<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Modules\Analytics\Application\DTOs\CLVSegmentEnum;

/**
 * CLV Experiment Designer
 *
 * Service for designing CLV-based A/B test experiments.
 * Provides recommendations for experiment parameters based on seller's CLV data.
 * 
 * Production-ready with:
 * - Automatic segment analysis
 * - Recommended experiment configurations
 * - Power analysis for sample size estimation
 * - Best practices from Alibaba/Ozon experience
 * 
 * @see https://github.com/nanolord126/CatVRF
 */
final readonly class CLVExperimentDesigner
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly SellerCLVService $clvService,
    ) {}

    /**
     * Design a CLV-based discount experiment for a seller.
     * 
     * Analyzes seller's CLV distribution and recommends optimal experiment parameters.
     * 
     * @param int $sellerId Seller ID
     * @param int $tenantId Tenant ID
     * @param array $options Additional options (discount levels, duration, etc.)
     * @return array Experiment configuration
     */
    public function designDiscountExperiment(
        int $sellerId,
        int $tenantId,
        array $options = [],
    ): array {
        $this->logAction('clv_experiment_design', [
            'seller_id' => $sellerId,
            'tenant_id' => $tenantId,
            'options' => $options,
        ]);

        // Get CLV segment distribution
        $segmentDistribution = $this->clvService->getSegmentDistribution($sellerId, $tenantId);
        $aggregatedMetrics = $this->clvService->getAggregatedCLVMetrics($sellerId, $tenantId);

        // Determine target segment based on options or auto-select
        $targetSegment = $options['target_segment'] ?? $this->selectBestSegment($segmentDistribution);

        // Generate experiment key
        $experimentKey = $this->generateExperimentKey($sellerId, $targetSegment, 'discount');

        // Calculate CLV filters
        $clvFilters = $this->generateCLVFilters($targetSegment, $aggregatedMetrics);

        // Generate variant configurations
        $variants = $this->generateDiscountVariants($options['discounts'] ?? [10, 15, 0]);

        // Calculate recommended sample size
        $sampleSize = $this->calculateSampleSize(
            $segmentDistribution[$targetSegment]['count'] ?? 0,
            $options['confidence_level'] ?? 0.95,
            $options['effect_size'] ?? 0.1,
        );

        // Calculate traffic percentage
        $trafficPercent = $this->calculateTrafficPercent(
            $sampleSize,
            $segmentDistribution[$targetSegment]['count'] ?? 0,
        );

        return [
            'tenant_id' => $tenantId,
            'seller_id' => $sellerId,
            'key' => $experimentKey,
            'name' => $options['name'] ?? "CLV Discount Test - {$targetSegment}",
            'description' => $options['description'] ?? "Testing discount effectiveness for {$targetSegment} segment",
            'target_segment' => $targetSegment,
            'clv_filters' => $clvFilters,
            'traffic_percent' => $trafficPercent,
            'scheduled_start_at' => $options['start_date'] ?? now()->addDay(),
            'scheduled_end_at' => $options['end_date'] ?? now()->addDays(14),
            'primary_metric' => 'revenue_14d',
            'secondary_metrics' => ['orders_count', 'clv_delta', 'churn_prob_delta'],
            'variants' => $variants,
            'metadata' => [
                'design_version' => '1.0',
                'sample_size_recommended' => $sampleSize,
                'segment_distribution' => $segmentDistribution,
                'power_analysis' => [
                    'confidence_level' => $options['confidence_level'] ?? 0.95,
                    'effect_size' => $options['effect_size'] ?? 0.1,
                    'power' => 0.8,
                ],
            ],
        ];
    }

    /**
     * Design a churn prevention experiment.
     * 
     * Tests different retention strategies for high-churn-risk buyers.
     */
    public function designChurnPreventionExperiment(
        int $sellerId,
        int $tenantId,
        array $options = [],
    ): array {
        $this->logAction('clv_churn_experiment_design', [
            'seller_id' => $sellerId,
            'tenant_id' => $tenantId,
        ]);

        // Get high churn risk buyers
        $highChurnBuyers = $this->clvService->getHighChurnRiskBuyers(
            $sellerId,
            $tenantId,
            $options['churn_threshold'] ?? 0.5,
            1000,
        );

        $experimentKey = $this->generateExperimentKey($sellerId, 'at_risk', 'churn');

        $variants = $this->generateChurnVariants($options['strategies'] ?? ['discount', 'free_shipping', 'control']);

        return [
            'tenant_id' => $tenantId,
            'seller_id' => $sellerId,
            'key' => $experimentKey,
            'name' => $options['name'] ?? "Churn Prevention Test - At Risk",
            'description' => "Testing retention strategies for high-churn-risk buyers",
            'target_segment' => 'at_risk',
            'clv_filters' => [
                'churn_prob_max' => $options['churn_threshold'] ?? 0.5,
                'clv_180d_min' => $options['min_clv'] ?? 1000,
            ],
            'traffic_percent' => $options['traffic_percent'] ?? 50,
            'scheduled_start_at' => $options['start_date'] ?? now()->addDay(),
            'scheduled_end_at' => $options['end_date'] ?? now()->addDays(30),
            'primary_metric' => 'churn_prob_delta',
            'secondary_metrics' => ['revenue_30d', 'clv_delta', 'retention_rate'],
            'variants' => $variants,
            'metadata' => [
                'high_churn_count' => count($highChurnBuyers),
                'design_type' => 'churn_prevention',
            ],
        ];
    }

    /**
     * Design a message personalization experiment.
     * 
     * Tests different message personalization strategies.
     */
    public function designPersonalizationExperiment(
        int $sellerId,
        int $tenantId,
        array $options = [],
    ): array {
        $experimentKey = $this->generateExperimentKey($sellerId, 'high_clv', 'personalization');

        $variants = $this->generatePersonalizationVariants($options['personalization_types'] ?? ['generic', 'category_based', 'purchase_history']);

        return [
            'tenant_id' => $tenantId,
            'seller_id' => $sellerId,
            'key' => $experimentKey,
            'name' => $options['name'] ?? "Message Personalization Test",
            'description' => "Testing message personalization effectiveness",
            'target_segment' => 'high_clv',
            'clv_filters' => [
                'clv_180d_min' => $options['min_clv'] ?? 5000,
            ],
            'traffic_percent' => $options['traffic_percent'] ?? 30,
            'scheduled_start_at' => $options['start_date'] ?? now()->addDay(),
            'scheduled_end_at' => $options['end_date'] ?? now()->addDays(7),
            'primary_metric' => 'conversion_rate',
            'secondary_metrics' => ['click_through_rate', 'revenue_7d'],
            'variants' => $variants,
            'metadata' => [
                'design_type' => 'personalization',
            ],
        ];
    }

    /**
     * Select the best segment for experimentation based on distribution.
     */
    private function selectBestSegment(array $distribution): string
    {
        // Prioritize segments with sufficient sample size
        $minSampleSize = 300;

        foreach ($distribution as $segment => $data) {
            if ($data['count'] >= $minSampleSize) {
                // Prefer high-value segments
                if (in_array($segment, ['vip', 'high_clv', 'champions'])) {
                    return $segment;
                }
            }
        }

        // Fallback to largest segment
        $largestSegment = null;
        $maxCount = 0;

        foreach ($distribution as $segment => $data) {
            if ($data['count'] > $maxCount) {
                $maxCount = $data['count'];
                $largestSegment = $segment;
            }
        }

        return $largestSegment ?? 'unknown';
    }

    /**
     * Generate CLV filters for a segment.
     */
    private function generateCLVFilters(string $segment, array $aggregatedMetrics): array
    {
        $filters = ['segment' => $segment];

        $avgClv = $aggregatedMetrics['avg_clv_180d'] ?? 0;

        return match ($segment) {
            'vip' => [
                'segment' => 'vip',
                'clv_180d_min' => $avgClv * 2,
            ],
            'high_clv' => [
                'segment' => 'high_clv',
                'clv_180d_min' => $avgClv,
            ],
            'at_risk' => [
                'segment' => 'at_risk',
                'churn_prob_max' => 0.5,
                'clv_180d_min' => $avgClv * 0.5,
            ],
            default => [
                'segment' => $segment,
            ],
        };
    }

    /**
     * Generate discount variant configurations.
     */
    private function generateDiscountVariants(array $discounts): array
    {
        $variants = [];
        $trafficPerVariant = intval(100 / count($discounts));

        foreach ($discounts as $index => $discount) {
            $isControl = $discount === 0;
            
            $variants[] = [
                'key' => chr(65 + $index), // A, B, C, ...
                'name' => $isControl ? 'Control (No Discount)' : "{$discount}% Discount",
                'configuration' => [
                    'discount' => $discount,
                    'message' => $isControl 
                        ? 'Regular price' 
                        : "Save {$discount}% on your next order!",
                    'coupon_code' => $discount > 0 ? "SAVE{$discount}" : null,
                ],
                'traffic_allocation' => $trafficPerVariant,
                'is_control' => $isControl,
            ];
        }

        return $variants;
    }

    /**
     * Generate churn prevention variant configurations.
     */
    private function generateChurnVariants(array $strategies): array
    {
        $variants = [];
        $trafficPerVariant = intval(100 / count($strategies));

        foreach ($strategies as $index => $strategy) {
            $config = match ($strategy) {
                'discount' => [
                    'discount' => 20,
                    'message' => 'We miss you! 20% off your next order',
                    'coupon_code' => 'COMEBACK20',
                ],
                'free_shipping' => [
                    'free_shipping' => true,
                    'message' => 'Free shipping on your next order',
                ],
                default => [
                    'discount' => 0,
                    'message' => 'Control - no intervention',
                ],
            };

            $variants[] = [
                'key' => chr(65 + $index),
                'name' => ucfirst(str_replace('_', ' ', $strategy)),
                'configuration' => $config,
                'traffic_allocation' => $trafficPerVariant,
                'is_control' => $strategy === 'control',
            ];
        }

        return $variants;
    }

    /**
     * Generate personalization variant configurations.
     */
    private function generatePersonalizationVariants(array $types): array
    {
        $variants = [];
        $trafficPerVariant = intval(100 / count($types));

        foreach ($types as $index => $type) {
            $config = match ($type) {
                'generic' => [
                    'personalization_level' => 'none',
                    'message' => 'Check out our latest deals!',
                ],
                'category_based' => [
                    'personalization_level' => 'category',
                    'message' => 'Deals in your favorite categories',
                ],
                'purchase_history' => [
                    'personalization_level' => 'full',
                    'message' => 'Based on your purchase history',
                ],
                default => [
                    'personalization_level' => 'none',
                    'message' => 'Generic message',
                ],
            };

            $variants[] = [
                'key' => chr(65 + $index),
                'name' => ucfirst(str_replace('_', ' ', $type)),
                'configuration' => $config,
                'traffic_allocation' => $trafficPerVariant,
                'is_control' => $type === 'generic',
            ];
        }

        return $variants;
    }

    /**
     * Calculate required sample size using power analysis.
     * 
     * Simplified formula: n = (Z_α/2 + Z_β)² * 2 * σ² / Δ²
     * Where:
     * - Z_α/2 = 1.96 for 95% confidence
     * - Z_β = 0.84 for 80% power
     * - σ² = variance (assumed 1 for relative effect size)
     * - Δ = effect size
     */
    private function calculateSampleSize(
        int $populationSize,
        float $confidenceLevel = 0.95,
        float $effectSize = 0.1,
    ): int {
        $zAlpha = match ($confidenceLevel) {
            0.99 => 2.576,
            0.95 => 1.96,
            0.90 => 1.645,
            default => 1.96,
        };

        $zBeta = 0.84; // 80% power

        // Simplified calculation (relative effect size)
        $n = pow($zAlpha + $zBeta, 2) * 2 / pow($effectSize, 2);

        // Adjust for finite population
        if ($populationSize > 0) {
            $n = $n / (1 + ($n - 1) / $populationSize);
        }

        // Minimum sample size per variant
        $n = max(300, $n);

        return (int) ceil($n);
    }

    /**
     * Calculate traffic percentage based on sample size and population.
     */
    private function calculateTrafficPercent(int $sampleSize, int $populationSize): int
    {
        if ($populationSize === 0) {
            return 100;
        }

        $percent = ($sampleSize / $populationSize) * 100;

        // Cap at 100%, minimum 10%
        return (int) min(100, max(10, $percent));
    }

    /**
     * Generate unique experiment key.
     */
    private function generateExperimentKey(int $sellerId, string $segment, string $type): string
    {
        $timestamp = now()->format('Ymd');
        $random = substr(md5(uniqid()), 0, 6);

        return strtolower("{$sellerId}_{$segment}_{$type}_{$timestamp}_{$random}");
    }
}
