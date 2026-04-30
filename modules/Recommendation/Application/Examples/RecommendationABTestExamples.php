<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Examples;

use Illuminate\Support\Facades\Log;
use Modules\Analytics\Facades\ABTest;
use Modules\Recommendation\Application\Services\RecommendationFacade;
use Modules\Recommendation\Application\DTOs\RecommendationRequestDTO;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;

final class RecommendationABTestExamples
{
    /**
     * Example 1: A/B Test for Two-tower vs Collaborative Filtering on Home Feed
     * 
     * Hypothesis: Two-tower model with personalization will increase CTR by 5% compared to collaborative filtering
     * Metrics: CTR, CVR, GMV lift
     * Duration: 7 days
     * Sample size: 50,000 users per variant
     */
    public function testTwoTowerVsCollaborative(): void
    {
        $experimentId = ABTest::createExperiment(
            name: 'recommendation_two_tower_vs_collaborative',
            description: 'Test two-tower personalization vs collaborative filtering on home feed',
            variants: [
                'control' => ['model' => 'collaborative', 'weight' => 0.5],
                'treatment' => ['model' => 'two_tower', 'weight' => 0.5],
            ],
            stratification: 'clv',
            minSampleSize: 50000,
            durationDays: 7,
        );

        $userId = auth()->id();
        $tenantId = tenant()->id;

        $variant = ABTest::assign(
            experimentId: $experimentId,
            userId: $userId,
            context: ['clv_segment' => $this->getCLVSegment($userId)],
        );

        $request = RecommendationRequestDTO::forHomeFeed(
            $tenantId,
            $userId,
            context: ['ab_test' => $variant],
        );

        $response = RecommendationFacade::forUser($userId)
            ->withSource($variant === 'treatment' 
                ? \Modules\Recommendation\Domain\Enums\RecommendationSource::TWO_TOWER
                : \Modules\Recommendation\Domain\Enums\RecommendationSource::COLLABORATIVE
            )
            ->getHomeFeed($tenantId);

        ABTest::recordExposure($experimentId, $userId, $variant);

        Log::info('A/B test variant assigned', [
            'experiment' => $experimentId,
            'user_id' => $userId,
            'variant' => $variant,
            'item_count' => $response->itemCount(),
        ]);
    }

    /**
     * Example 2: Multi-Armed Bandit for Exploration vs Exploitation
     * 
     * Uses Thompson Sampling to balance exploring new items vs exploiting known high-performing items
     * Continuously updates based on observed CTR
     */
    public function testBanditExploration(): void
    {
        $experimentId = 'recommendation_bandit_exploration';
        $userId = auth()->id();
        $tenantId = tenant()->id;

        $arm = ABTest::assignBandit(
            experimentId: $experimentId,
            userId: $userId,
            arms: [
                'exploit' => ['exploration_rate' => 0.05],
                'explore' => ['exploration_rate' => 0.20],
                'balanced' => ['exploration_rate' => 0.10],
            ],
            algorithm: 'thompson_sampling',
        );

        $response = RecommendationFacade::forUser($userId)
            ->getHomeFeed($tenantId, context: ['bandit_arm' => $arm]);

        $this->trackBanditFeedback($experimentId, $userId, $arm, $response);
    }

    /**
     * Example 3: Fairness Constraint Test
     * 
     * Test if enforcing seller fairness affects GMV
     * Control: No fairness constraints
     * Treatment: Min seller exposure 5%, max dominance 30%
     */
    public function testFairnessConstraints(): void
    {
        $experimentId = ABTest::createExperiment(
            name: 'recommendation_fairness_gmv_impact',
            description: 'Test impact of seller fairness constraints on GMV',
            variants: [
                'control' => ['fairness' => 'none'],
                'treatment' => ['fairness' => 'strict'],
            ],
            durationDays: 14,
        );

        $userId = auth()->id();
        $tenantId = tenant()->id;
        $variant = ABTest::assign($experimentId, $userId);

        $response = RecommendationFacade::forUser($userId)
            ->getHomeFeed(
                $tenantId,
                context: [
                    'fairness_mode' => $variant === 'treatment' ? 'strict' : 'none',
                ]
            );

        return $response;
    }

    /**
     * Example 4: Model Version A/B Test (Canary Deployment)
     * 
     * Gradually roll out new model version
     * 10% traffic -> 25% -> 50% -> 100% based on metrics
     */
    public function testModelVersionCanary(): void
    {
        $experimentId = 'recommendation_model_v2_canary';
        $userId = auth()->id();
        $tenantId = tenant()->id;

        $variant = ABTest::assign(
            experimentId: $experimentId,
            userId: $userId,
            variants: [
                'v1' => ['version' => 'v1.0.0', 'weight' => 0.9],
                'v2' => ['version' => 'v2.0.0', 'weight' => 0.1],
            ],
        );

        $response = RecommendationFacade::forUser($userId)
            ->getHomeFeed($tenantId, context: ['model_version' => $variant]);

        $this->trackModelMetrics($variant, $response->itemCount(), $response->latencyMs);
    }

    /**
     * Example 5: Scenario-Specific Optimization
     * 
     * Test different recommendation sources for different scenarios
     */
    public function testScenarioOptimization(): void
    {
        $scenarios = [
            RecommendationScenario::HOME_FEED => ['two_tower', 'trending'],
            RecommendationScenario::PRODUCT_DETAIL => ['similar_items', 'collaborative'],
            RecommendationScenario::CART => ['frequently_bought', 'content_based'],
        ];

        foreach ($scenarios as $scenario => $sources) {
            $experimentId = "recommendation_scenario_{$scenario->value}";
            $variant = ABTest::assign($experimentId, auth()->id(), variants: $sources);

            $request = new RecommendationRequestDTO(
                tenantId: tenant()->id,
                userId: auth()->id(),
                scenario: $scenario,
                context: ['ab_source' => $variant],
            );

            $response = RecommendationFacade::forUser(auth()->id())
                ->getRecommendations($request);
        }
    }

    /**
     * Calculate success metrics for A/B test
     */
    public function calculateTestMetrics(string $experimentId): array
    {
        $results = ABTest::getResults($experimentId);

        $metrics = [
            'experiment_id' => $experimentId,
            'total_users' => $results['total_users'] ?? 0,
            'variants' => [],
        ];

        foreach ($results['variants'] ?? [] as $variant => $data) {
            $impressions = $data['impressions'] ?? 0;
            $clicks = $data['clicks'] ?? 0;
            $conversions = $data['conversions'] ?? 0;
            $revenue = $data['revenue'] ?? 0.0;

            $metrics['variants'][$variant] = [
                'users' => $data['users'] ?? 0,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversions' => $conversions,
                'revenue' => round($revenue, 2),
                'ctr' => $impressions > 0 ? round($clicks / $impressions, 4) : 0.0,
                'cvr' => $clicks > 0 ? round($conversions / $clicks, 4) : 0.0,
                'revenue_per_user' => ($data['users'] ?? 0) > 0 ? round($revenue / $data['users'], 2) : 0.0,
            ];
        }

        $metrics['statistical_significance'] = $this->calculateSignificance($metrics);

        return $metrics;
    }

    private function calculateSignificance(array $metrics): array
    {
        if (count($metrics['variants']) < 2) {
            return ['significant' => false, 'p_value' => null];
        }

        $variants = array_values($metrics['variants']);
        $control = $variants[0];
        $treatment = $variants[1];

        $n1 = $control['impressions'] ?? 1;
        $n2 = $treatment['impressions'] ?? 1;
        $p1 = $control['ctr'] ?? 0;
        $p2 = $treatment['ctr'] ?? 0;

        $pooled = ($p1 * $n1 + $p2 * $n2) / ($n1 + $n2);
        $se = sqrt($pooled * (1 - $pooled) * (1 / $n1 + 1 / $n2));
        $z = $se > 0 ? ($p2 - $p1) / $se : 0;

        $pValue = 2 * (1 - $this->normalCDF(abs($z)));

        return [
            'significant' => $pValue < 0.05,
            'p_value' => round($pValue, 4),
            'z_score' => round($z, 4),
            'lift' => $p1 > 0 ? round(($p2 - $p1) / $p1 * 100, 2) : 0.0,
        ];
    }

    private function normalCDF(float $z): float
    {
        $t = 1 / (1 + 0.2316419 * abs($z));
        $d = 0.3989423 * exp(-$z * $z / 2);
        $prob = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
        return $z > 0 ? 1 - $prob : $prob;
    }

    private function getCLVSegment(int $userId): string
    {
        $clv = \Modules\Analytics\Facades\SellerAnalyticsFacade::getCLVPrediction($userId);
        
        return match (true) {
            $clv > 1000 => 'high',
            $clv > 500 => 'medium',
            default => 'low',
        };
    }

    private function trackBanditFeedback(string $experimentId, int $userId, string $arm, $response): void
    {
        // Track CTR for bandit arm
        ABTest::trackMetric($experimentId, $userId, $arm, 'impression');
    }

    private function trackModelMetrics(string $version, int $itemCount, float $latency): void
    {
        // Track to BigData monitoring
        \Modules\BigData\Application\Services\BigDataFacade::trackEvent('recommendation_model_served', [
            'version' => $version,
            'item_count' => $itemCount,
            'latency_ms' => $latency,
        ]);
    }
}
