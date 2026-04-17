<?php declare(strict_types=1);

namespace Tests\Unit\Services\ML;

use Tests\TestCase;
use App\Services\ML\FraudMLExplainer;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class FraudMLExplainerTest extends TestCase
{
    use RefreshDatabase;

    private FraudMLExplainer $explainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->explainer = app(FraudMLExplainer::class);
    }

    public function test_explain_prediction_with_low_score_returns_no_explanation(): void
    {
        $features = [
            'amount_log' => 5.0,
            'hour_of_day' => 14,
            'tx_count_24h' => 2,
        ];

        $result = $this->explainer->explainPrediction($features, 0.5, 'test-v1');

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('top_features', $result);
        $this->assertArrayHasKey('threshold_exceeded', $result);
        $this->assertEquals(0.5, $result['score']);
        $this->assertEmpty($result['top_features']);
        $this->assertFalse($result['threshold_exceeded']);
    }

    public function test_explain_prediction_with_high_score_returns_explanation(): void
    {
        $features = [
            'amount_log' => 5.0,
            'hour_of_day' => 14,
            'tx_count_24h' => 10,
            'current_quota_usage_ratio' => 0.98,
        ];

        $result = $this->explainer->explainPrediction($features, 0.85, 'test-v1');

        $this->assertEquals(0.85, $result['score']);
        $this->assertTrue($result['threshold_exceeded']);
        $this->assertNotEmpty($result['top_features']);
        $this->assertLessThanOrEqual(5, count($result['top_features']));
    }

    public function test_explain_prediction_returns_top_features(): void
    {
        $features = [
            'amount_log' => 8.0,
            'hour_of_day' => 3,
            'tx_count_24h' => 15,
            'current_quota_usage_ratio' => 0.99,
            'ip_risk_score' => 0.9,
        ];

        $result = $this->explainer->explainPrediction($features, 0.9, 'test-v1');

        $this->assertNotEmpty($result['top_features']);
        
        // Check that top features have required structure
        foreach ($result['top_features'] as $featureName => $featureData) {
            $this->assertArrayHasKey('value', $featureData);
            $this->assertArrayHasKey('feature_value', $featureData);
            $this->assertArrayHasKey('normalized_value', $featureData);
            $this->assertIsFloat($featureData['value']);
        }
    }

    public function test_explain_prediction_includes_model_version(): void
    {
        $features = ['amount_log' => 5.0];
        $modelVersion = '2026-04-17-v1';

        $result = $this->explainer->explainPrediction($features, 0.8, $modelVersion);

        $this->assertArrayHasKey('model_version', $result);
        $this->assertEquals($modelVersion, $result['model_version']);
    }

    public function test_explain_prediction_includes_timestamp(): void
    {
        $features = ['amount_log' => 5.0];

        $result = $this->explainer->explainPrediction($features, 0.8, 'test-v1');

        $this->assertArrayHasKey('explanation_timestamp', $result);
        $this->assertNotEmpty($result['explanation_timestamp']);
    }

    public function test_format_explanation_for_human_with_low_score(): void
    {
        $explanation = [
            'score' => 0.3,
            'top_features' => [],
            'threshold_exceeded' => false,
        ];

        $formatted = $this->explainer->formatExplanationForHuman($explanation);

        $this->assertStringContainsString('Low risk prediction', $formatted);
        $this->assertStringContainsString('0.3', $formatted);
    }

    public function test_format_explanation_for_human_with_high_score(): void
    {
        $explanation = [
            'score' => 0.85,
            'top_features' => [
                'amount_log' => [
                    'value' => 0.3,
                    'feature_value' => 8.0,
                    'normalized_value' => 0.8,
                ],
                'tx_count_24h' => [
                    'value' => 0.25,
                    'feature_value' => 15,
                    'normalized_value' => 0.9,
                ],
            ],
            'threshold_exceeded' => true,
        ];

        $formatted = $this->explainer->formatExplanationForHuman($explanation);

        $this->assertStringContainsString('High risk prediction', $formatted);
        $this->assertStringContainsString('0.85', $formatted);
        $this->assertStringContainsString('Top contributing factors', $formatted);
        $this->assertStringContainsString('amount_log', $formatted);
        $this->assertStringContainsString('tx_count_24h', $formatted);
    }

    public function test_get_feature_importance_summary(): void
    {
        $explanations = [
            [
                'score' => 0.8,
                'top_features' => [
                    'amount_log' => ['value' => 0.3],
                    'tx_count_24h' => ['value' => 0.25],
                ],
            ],
            [
                'score' => 0.75,
                'top_features' => [
                    'amount_log' => ['value' => 0.28],
                    'hour_of_day' => ['value' => 0.2],
                ],
            ],
            [
                'score' => 0.9,
                'top_features' => [
                    'amount_log' => ['value' => 0.32],
                    'tx_count_24h' => ['value' => 0.27],
                ],
            ],
        ];

        $summary = $this->explainer->getFeatureImportanceSummary($explanations);

        $this->assertArrayHasKey('amount_log', $summary);
        $this->assertArrayHasKey('tx_count_24h', $summary);
        $this->assertArrayHasKey('hour_of_day', $summary);

        $this->assertArrayHasKey('count', $summary['amount_log']);
        $this->assertArrayHasKey('total_impact', $summary['amount_log']);
        $this->assertArrayHasKey('avg_impact', $summary['amount_log']);

        // amount_log should appear in all 3 explanations
        $this->assertEquals(3, $summary['amount_log']['count']);
        
        // tx_count_24h should appear in 2 explanations
        $this->assertEquals(2, $summary['tx_count_24h']['count']);
    }

    public function test_shap_threshold_constant(): void
    {
        $reflection = new \ReflectionClass($this->explainer);
        $threshold = $reflection->getConstant('SHAP_THRESHOLD');

        $this->assertEquals(0.7, $threshold);
    }

    public function test_top_features_count_constant(): void
    {
        $reflection = new \ReflectionClass($this->explainer);
        $count = $reflection->getConstant('TOP_FEATURES_COUNT');

        $this->assertEquals(5, $count);
    }

    public function test_explain_prediction_with_empty_features(): void
    {
        $result = $this->explainer->explainPrediction([], 0.8, 'test-v1');

        $this->assertEquals(0.8, $result['score']);
        $this->assertTrue($result['threshold_exceeded']);
        // Should still return empty array, not error
        $this->assertIsArray($result['top_features']);
    }

    public function test_explain_prediction_with_unknown_features(): void
    {
        $features = [
            'unknown_feature_1' => 100,
            'unknown_feature_2' => 200,
        ];

        $result = $this->explainer->explainPrediction($features, 0.8, 'test-v1');

        // Should handle gracefully without error
        $this->assertEquals(0.8, $result['score']);
        $this->assertTrue($result['threshold_exceeded']);
    }
}
