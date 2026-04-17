<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use App\Domains\FraudML\Services\FeatureDriftDetectorService;
use App\Domains\FraudML\Services\PrometheusMetricsService;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * FeatureDriftDetectorServiceTest — comprehensive unit tests for feature drift detection
 * 
 * Tests cover:
 * - PSI calculation with various distributions
 * - KS-test calculation with edge cases
 * - JS divergence calculation
 * - Combined drift score calculation
 * - Redis cache operations for reference distributions
 * - Edge cases (empty arrays, single value, identical distributions)
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class FeatureDriftDetectorServiceTest extends TestCase
{
    private FeatureDriftDetectorService $driftDetector;
    private PrometheusMetricsService $prometheus;

    protected function setUp(): void
    {
        parent::setUp();

        $redis = $this->createMock(RedisFactory::class);
        $logger = $this->createMock(LogManager::class);
        $this->prometheus = $this->createMock(PrometheusMetricsService::class);

        $this->driftDetector = new FeatureDriftDetectorService(
            $redis,
            $logger,
            $this->prometheus
        );

        Cache::flush();
    }

    public function test_calculate_psi_with_similar_distributions(): void
    {
        $expected = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $actual = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $result = $this->driftDetector->calculatePSI($expected, $actual);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('psi', $result);
        $this->assertArrayHasKey('interpretation', $result);
        $this->assertArrayHasKey('drift_detected', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertLessThan(0.1, $result['psi']); // Should be low for identical distributions
        $this->assertFalse($result['drift_detected']);
        $this->assertEquals('LOW', $result['severity']);
    }

    public function test_calculate_psi_with_different_distributions(): void
    {
        $expected = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $actual = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]; // Shifted distribution

        $result = $this->driftDetector->calculatePSI($expected, $actual);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0.1, $result['psi']); // Should detect drift
        $this->assertTrue($result['drift_detected'] || $result['severity'] === 'MEDIUM');
    }

    public function test_calculate_psi_with_custom_bins(): void
    {
        $expected = array_merge(range(1, 50), range(1, 50));
        $actual = array_merge(range(10, 60), range(10, 60));

        $result5Bins = $this->driftDetector->calculatePSI($expected, $actual, 5);
        $result20Bins = $this->driftDetector->calculatePSI($expected, $actual, 20);

        $this->assertIsArray($result5Bins);
        $this->assertIsArray($result20Bins);
        $this->assertEquals(5, $result5Bins['bins']);
        $this->assertEquals(20, $result20Bins['bins']);
    }

    public function test_calculate_psi_throws_exception_for_empty_arrays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->driftDetector->calculatePSI([], [1, 2, 3]);
    }

    public function test_calculate_ks_with_similar_distributions(): void
    {
        $expected = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $actual = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $result = $this->driftDetector->calculateKS($expected, $actual);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ks_statistic', $result);
        $this->assertArrayHasKey('p_value', $result);
        $this->assertArrayHasKey('interpretation', $result);
        $this->assertArrayHasKey('drift_detected', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertLessThan(0.5, $result['ks_statistic']); // Should be low for identical distributions
        $this->assertGreaterThan(0.05, $result['p_value']); // Should not detect drift
        $this->assertFalse($result['drift_detected']);
        $this->assertEquals('LOW', $result['severity']);
    }

    public function test_calculate_ks_with_different_distributions(): void
    {
        $expected = [1, 2, 3, 4, 5];
        $actual = [100, 200, 300, 400, 500]; // Completely different

        $result = $this->driftDetector->calculateKS($expected, $actual);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0.5, $result['ks_statistic']); // Should be high for different distributions
        $this->assertLessThan(0.05, $result['p_value']); // Should detect drift
        $this->assertTrue($result['drift_detected']);
    }

    public function test_calculate_ks_with_custom_alpha(): void
    {
        $expected = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $actual = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11]; // Slightly shifted

        $resultAlpha005 = $this->driftDetector->calculateKS($expected, $actual, 0.05);
        $resultAlpha01 = $this->driftDetector->calculateKS($expected, $actual, 0.01);

        $this->assertIsArray($resultAlpha005);
        $this->assertIsArray($resultAlpha01);
        $this->assertEquals(0.05, $resultAlpha005['alpha']);
        $this->assertEquals(0.01, $resultAlpha01['alpha']);
    }

    public function test_calculate_ks_throws_exception_for_empty_arrays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->driftDetector->calculateKS([], [1, 2, 3]);
    }

    public function test_calculate_js_divergence_with_similar_distributions(): void
    {
        $expected = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $actual = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $result = $this->driftDetector->calculateJSDivergence($expected, $actual);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('js_divergence', $result);
        $this->assertArrayHasKey('js_distance', $result);
        $this->assertArrayHasKey('interpretation', $result);
        $this->assertArrayHasKey('drift_detected', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertLessThan(0.1, $result['js_divergence']); // Should be low for identical distributions
        $this->assertFalse($result['drift_detected']);
        $this->assertEquals('LOW', $result['severity']);
    }

    public function test_calculate_js_divergence_with_different_distributions(): void
    {
        $expected = [1, 2, 3, 4, 5];
        $actual = [100, 200, 300, 400, 500]; // Completely different

        $result = $this->driftDetector->calculateJSDivergence($expected, $actual);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0.1, $result['js_divergence']); // Should detect drift
        $this->assertTrue($result['drift_detected'] || $result['severity'] === 'MEDIUM');
    }

    public function test_calculate_js_divergence_with_custom_bins(): void
    {
        $expected = array_merge(range(1, 50), range(1, 50));
        $actual = array_merge(range(10, 60), range(10, 60));

        $result5Bins = $this->driftDetector->calculateJSDivergence($expected, $actual, 5);
        $result20Bins = $this->driftDetector->calculateJSDivergence($expected, $actual, 20);

        $this->assertIsArray($result5Bins);
        $this->assertIsArray($result20Bins);
        $this->assertEquals(5, $result5Bins['bins']);
        $this->assertEquals(20, $result20Bins['bins']);
    }

    public function test_calculate_js_divergence_throws_exception_for_empty_arrays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->driftDetector->calculateJSDivergence([], [1, 2, 3]);
    }

    public function test_detect_drift_for_single_feature(): void
    {
        $expected = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $actual = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $result = $this->driftDetector->detectDriftForFeature(
            'test_feature',
            'test_vertical',
            $expected,
            $actual
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('feature', $result);
        $this->assertArrayHasKey('vertical', $result);
        $this->assertArrayHasKey('psi', $result);
        $this->assertArrayHasKey('ks', $result);
        $this->assertArrayHasKey('js_divergence', $result);
        $this->assertArrayHasKey('combined', $result);
        $this->assertEquals('test_feature', $result['feature']);
        $this->assertEquals('test_vertical', $result['vertical']);
        $this->assertArrayHasKey('drift_detected', $result['combined']);
        $this->assertArrayHasKey('severity', $result['combined']);
    }

    public function test_detect_drift_for_single_feature_with_drift(): void
    {
        $expected = [1, 2, 3, 4, 5];
        $actual = [100, 200, 300, 400, 500]; // Significant drift

        $result = $this->driftDetector->detectDriftForFeature(
            'test_feature',
            'test_vertical',
            $expected,
            $actual
        );

        $this->assertIsArray($result);
        $this->assertTrue(
            $result['psi']['drift_detected'] || 
            $result['ks']['drift_detected'] || 
            $result['js_divergence']['drift_detected']
        );
    }

    public function test_detect_all_features(): void
    {
        $featuresData = [
            'feature_1' => [
                'expected' => [1, 2, 3, 4, 5],
                'actual' => [1, 2, 3, 4, 5],
            ],
            'feature_2' => [
                'expected' => [10, 20, 30, 40, 50],
                'actual' => [10, 20, 30, 40, 50],
            ],
            'feature_3' => [
                'expected' => [100, 200, 300, 400, 500],
                'actual' => [100, 200, 300, 400, 500],
            ],
        ];

        $result = $this->driftDetector->detectAllFeatures('test_vertical', $featuresData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('vertical', $result);
        $this->assertArrayHasKey('features', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals('test_vertical', $result['vertical']);
        $this->assertCount(3, $result['features']);
        $this->assertArrayHasKey('total_features', $result['summary']);
        $this->assertArrayHasKey('drift_detected_features', $result['summary']);
        $this->assertArrayHasKey('max_severity', $result['summary']);
        $this->assertArrayHasKey('max_drift_score', $result['summary']);
        $this->assertArrayHasKey('overall_drift_detected', $result['summary']);
        $this->assertEquals(3, $result['summary']['total_features']);
    }

    public function test_detect_all_features_with_missing_data(): void
    {
        $featuresData = [
            'feature_1' => [
                'expected' => [1, 2, 3, 4, 5],
                'actual' => [1, 2, 3, 4, 5],
            ],
            'feature_2' => [
                'expected' => [10, 20, 30, 40, 50],
                // Missing 'actual'
            ],
        ];

        $result = $this->driftDetector->detectAllFeatures('test_vertical', $featuresData);

        $this->assertIsArray($result);
        $this->assertCount(1, $result['features']); // Only feature_1 should be processed
        $this->assertEquals(1, $result['summary']['total_features']);
    }

    public function test_store_reference_distribution(): void
    {
        $referenceData = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $featureName = 'test_feature';
        $vertical = 'test_vertical';

        $result = $this->driftDetector->storeReferenceDistribution(
            $featureName,
            $vertical,
            $referenceData
        );

        $this->assertTrue($result);
    }

    public function test_get_reference_distribution(): void
    {
        $referenceData = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $featureName = 'test_feature';
        $vertical = 'test_vertical';

        // Store reference distribution
        $this->driftDetector->storeReferenceDistribution(
            $featureName,
            $vertical,
            $referenceData
        );

        // Retrieve reference distribution
        $retrievedData = $this->driftDetector->getReferenceDistribution(
            $featureName,
            $vertical
        );

        $this->assertIsArray($retrievedData);
        $this->assertEquals($referenceData, $retrievedData);
    }

    public function test_get_reference_distribution_returns_null_when_not_found(): void
    {
        $retrievedData = $this->driftDetector->getReferenceDistribution(
            'non_existent_feature',
            'non_existent_vertical'
        );

        $this->assertNull($retrievedData);
    }

    public function test_invalidate_reference_cache(): void
    {
        $referenceData = [1, 2, 3, 4, 5];
        $featureName = 'test_feature';
        $vertical = 'test_vertical';

        // Store reference distribution
        $this->driftDetector->storeReferenceDistribution(
            $featureName,
            $vertical,
            $referenceData
        );

        // Invalidate cache
        $this->driftDetector->invalidateReferenceCache($vertical);

        // Try to retrieve - should return null
        $retrievedData = $this->driftDetector->getReferenceDistribution(
            $featureName,
            $vertical
        );

        $this->assertNull($retrievedData);
    }

    public function test_combined_drift_score_calculation(): void
    {
        $expected = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $actual = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $result = $this->driftDetector->detectDriftForFeature(
            'test_feature',
            'test_vertical',
            $expected,
            $actual
        );

        $this->assertArrayHasKey('combined', $result);
        $this->assertArrayHasKey('score', $result['combined']);
        $this->assertArrayHasKey('drift_detected', $result['combined']);
        $this->assertArrayHasKey('severity', $result['combined']);
        $this->assertArrayHasKey('components', $result['combined']);
        $this->assertIsFloat($result['combined']['score']);
        $this->assertGreaterThanOrEqual(0, $result['combined']['score']);
        $this->assertLessThanOrEqual(1, $result['combined']['score']);
        $this->assertContains($result['combined']['severity'], ['LOW', 'MEDIUM', 'HIGH']);
    }

    public function test_combined_drift_score_with_high_drift(): void
    {
        $expected = [1, 2, 3, 4, 5];
        $actual = [100, 200, 300, 400, 500];

        $result = $this->driftDetector->detectDriftForFeature(
            'test_feature',
            'test_vertical',
            $expected,
            $actual
        );

        $this->assertArrayHasKey('combined', $result);
        $this->assertTrue($result['combined']['drift_detected']);
        $this->assertGreaterThan(0.5, $result['combined']['score']);
    }

    public function test_psi_thresholds(): void
    {
        // Test LOW severity
        $result = $this->driftDetector->calculatePSI(
            [1, 2, 3, 4, 5],
            [1, 2, 3, 4, 5]
        );
        $this->assertEquals('LOW', $result['severity']);

        // Test with distributions that might trigger MEDIUM/HIGH
        $result = $this->driftDetector->calculatePSI(
            [1, 2, 3, 4, 5],
            [1, 1, 1, 1, 100] // Skewed distribution
        );
        $this->assertContains($result['severity'], ['MEDIUM', 'HIGH']);
    }

    public function test_ks_severity_levels(): void
    {
        // Test LOW severity (similar distributions)
        $result = $this->driftDetector->calculateKS(
            [1, 2, 3, 4, 5],
            [1, 2, 3, 4, 5]
        );
        $this->assertEquals('LOW', $result['severity']);

        // Test HIGH severity (different distributions)
        $result = $this->driftDetector->calculateKS(
            [1, 2, 3],
            [100, 200, 300]
        );
        $this->assertEquals('HIGH', $result['severity']);
    }

    public function test_js_severity_levels(): void
    {
        // Test LOW severity (similar distributions)
        $result = $this->driftDetector->calculateJSDivergence(
            [1, 2, 3, 4, 5],
            [1, 2, 3, 4, 5]
        );
        $this->assertEquals('LOW', $result['severity']);

        // Test with different distributions
        $result = $this->driftDetector->calculateJSDivergence(
            [1, 2, 3],
            [100, 200, 300]
        );
        $this->assertContains($result['severity'], ['MEDIUM', 'HIGH']);
    }

    public function test_edge_case_single_value_arrays(): void
    {
        $expected = [5];
        $actual = [5];

        $psiResult = $this->driftDetector->calculatePSI($expected, $actual);
        $ksResult = $this->driftDetector->calculateKS($expected, $actual);
        $jsResult = $this->driftDetector->calculateJSDivergence($expected, $actual);

        $this->assertIsArray($psiResult);
        $this->assertIsArray($ksResult);
        $this->assertIsArray($jsResult);
    }

    public function test_edge_case_large_arrays(): void
    {
        $expected = range(1, 10000);
        $actual = range(1, 10000);

        $result = $this->driftDetector->calculatePSI($expected, $actual);

        $this->assertIsArray($result);
        $this->assertEquals(10000, $result['sample_size_expected']);
        $this->assertEquals(10000, $result['sample_size_actual']);
    }
}
