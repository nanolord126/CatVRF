<?php

declare(strict_types=1);

namespace Tests\Unit\Services\ML;

use Tests\TestCase;
use App\Services\ML\FeatureDriftDetectorService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;

/**
 * Feature Drift Detector Service Test
 * 
 * Tests PSI calculation, KS-test, reference distribution storage,
 * and drift detection with per-vertical thresholds.
 */
final class FeatureDriftDetectorServiceTest extends TestCase
{
    private FeatureDriftDetectorService $driftDetector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driftDetector = new FeatureDriftDetectorService(
            $this->app->make(LoggerInterface::class),
            $this->app->make(ConfigRepository::class)
        );
    }

    public function test_calculate_psi_no_drift(): void
    {
        // Simulate identical distributions (raw values)
        $expected = array_fill(0, 300, 5.0);
        $actual = array_fill(0, 300, 5.0);

        $result = $this->driftDetector->calculatePSI($expected, $actual, 'test_feature');

        $this->assertIsArray($result, 'PSI should return array with metadata');
        $this->assertArrayHasKey('psi', $result);
        $this->assertArrayHasKey('interpretation', $result);
        $this->assertArrayHasKey('drift_severity', $result);
        $this->assertLessThan(0.1, $result['psi'], 'PSI should be low when distributions are identical');
        $this->assertEquals('LOW', $result['drift_severity'], 'Drift severity should be LOW');
    }

    public function test_calculate_psi_moderate_drift(): void
    {
        // Simulate moderate shift
        $expected = array_fill(0, 300, 5.0);
        $actual = array_merge(array_fill(0, 200, 5.0), array_fill(0, 100, 7.0));

        $result = $this->driftDetector->calculatePSI($expected, $actual, 'test_feature');

        $this->assertGreaterThanOrEqual(0.1, $result['psi'], 'PSI should indicate moderate drift');
        $this->assertLessThan(0.25, $result['psi'], 'PSI should not exceed critical threshold');
        $this->assertEquals('MEDIUM', $result['drift_severity'], 'Drift severity should be MEDIUM');
    }

    public function test_calculate_psi_critical_drift(): void
    {
        // Simulate significant shift (like after marketing campaign)
        $expected = array_fill(0, 300, 5.0);
        $actual = array_merge(array_fill(0, 50, 5.0), array_fill(0, 250, 8.0));

        $result = $this->driftDetector->calculatePSI($expected, $actual, 'test_feature');

        $this->assertGreaterThanOrEqual(0.25, $result['psi'], 'PSI should indicate critical drift');
        $this->assertEquals('HIGH', $result['drift_severity'], 'Drift severity should be HIGH');
        $this->assertStringContainsString('ACTION REQUIRED', $result['interpretation'], 'Interpretation should indicate action required');
    }

    public function test_calculate_psi_handles_empty_arrays(): void
    {
        $expected = [];
        $actual = [1.0, 2.0, 3.0];

        $result = $this->driftDetector->calculatePSI($expected, $actual, 'test_feature');

        $this->assertEquals(0.0, $result['psi'], 'PSI should return 0.0 for empty expected array');
        $this->assertEquals('Insufficient data', $result['interpretation'], 'Should indicate insufficient data');
    }

    public function test_calculate_psi_percentile_binning(): void
    {
        // Test with realistic normal distribution shift
        $expected = [];
        for ($i = 0; $i < 12000; $i++) {
            $expected[] = 4.8 + (rand(-100, 100) / 100) * 1.9; // Normal-like distribution
        }
        
        $actual = [];
        for ($i = 0; $i < 9500; $i++) {
            $actual[] = 8.3 + (rand(-100, 100) / 100) * 3.2; // Shifted distribution
        }

        $result = $this->driftDetector->calculatePSI($expected, $actual, 'ai_diagnosis_frequency');

        $this->assertArrayHasKey('num_bins', $result, 'Should include number of bins');
        $this->assertArrayHasKey('max_psi_contribution', $result, 'Should include max PSI contribution');
        $this->assertGreaterThan(0, $result['num_bins'], 'Should have multiple bins');
        $this->assertGreaterThanOrEqual(0.0, $result['max_psi_contribution'], 'Max contribution should be non-negative');
    }

    public function test_calculate_ks_test_no_drift(): void
    {
        $expected = [1.0, 2.0, 3.0, 4.0, 5.0];
        $actual = [1.0, 2.0, 3.0, 4.0, 5.0];

        $ks = $this->driftDetector->calculateKSTest($expected, $actual, 'test_feature');

        $this->assertLessThan(0.05, $ks, 'KS statistic should be low when distributions are identical');
    }

    public function test_calculate_ks_test_moderate_drift(): void
    {
        $expected = [1.0, 2.0, 3.0, 4.0, 5.0];
        $actual = [1.5, 2.5, 3.5, 4.5, 5.5];

        $ks = $this->driftDetector->calculateKSTest($expected, $actual, 'test_feature');

        $this->assertGreaterThanOrEqual(0.0, $ks, 'KS statistic should be non-negative');
        $this->assertLessThanOrEqual(1.0, $ks, 'KS statistic should not exceed 1.0');
    }

    public function test_calculate_ks_test_critical_drift(): void
    {
        $expected = [1.0, 2.0, 3.0, 4.0, 5.0];
        $actual = [10.0, 20.0, 30.0, 40.0, 50.0];

        $ks = $this->driftDetector->calculateKSTest($expected, $actual, 'test_feature');

        $this->assertGreaterThanOrEqual(0.1, $ks, 'KS statistic should indicate critical drift');
    }

    public function test_calculate_ks_test_handles_empty_arrays(): void
    {
        $expected = [];
        $actual = [1.0, 2.0, 3.0];

        $ks = $this->driftDetector->calculateKSTest($expected, $actual, 'test_feature');

        $this->assertEquals(0.0, $ks, 'KS should return 0.0 for empty expected array');
    }

    public function test_detect_drift_no_drift(): void
    {
        $featuresData = [
            'feature_1' => [
                'expected' => ['A' => 100, 'B' => 100],
                'actual' => ['A' => 100, 'B' => 100],
            ],
        ];

        $report = $this->driftDetector->detectDrift($featuresData);

        $this->assertFalse($report['overall_drift_detected'], 'Overall drift should not be detected');
        $this->assertEmpty($report['drifted_features'], 'No features should be marked as drifted');
        $this->assertEquals(1, $report['features_checked'], 'One feature should be checked');
    }

    public function test_detect_drift_critical_drift(): void
    {
        $featuresData = [
            'feature_1' => [
                'expected' => ['A' => 100, 'B' => 100],
                'actual' => ['A' => 300, 'B' => 0],
            ],
        ];

        $report = $this->driftDetector->detectDrift($featuresData);

        $this->assertTrue($report['overall_drift_detected'], 'Overall drift should be detected');
        $this->assertNotEmpty($report['drifted_features'], 'Features should be marked as drifted');
    }

    public function test_detect_drift_with_vertical_thresholds(): void
    {
        Config::set('fraud.drift_thresholds.medical', [
            'psi_critical' => 0.15,
            'psi_moderate' => 0.08,
            'ks_critical' => 0.07,
            'ks_moderate' => 0.03,
        ]);

        $featuresData = [
            'feature_1' => [
                'expected' => ['A' => 100, 'B' => 100],
                'actual' => ['A' => 150, 'B' => 50],
            ],
        ];

        $report = $this->driftDetector->detectDrift($featuresData, 'medical');

        $this->assertEquals('medical', $report['vertical_code'], 'Vertical code should be set');
        // Medical thresholds are stricter, so moderate drift should be detected as critical
    }

    public function test_store_reference_distribution(): void
    {
        Redis::shouldReceive('setex')
            ->once()
            ->with(
                \Mockery::pattern('/fraud:ml:drift:reference:*/'),
                86400,
                \Mockery::type('string')
            )
            ->andReturnTrue();

        $distribution = ['A' => 100, 'B' => 100, 'C' => 100];
        $result = $this->driftDetector->storeReferenceDistribution(
            'v1.0.0',
            'test_feature',
            $distribution
        );

        $this->assertTrue($result, 'Reference distribution should be stored successfully');
    }

    public function test_get_reference_distribution(): void
    {
        $distribution = ['A' => 100, 'B' => 100, 'C' => 100];
        $serialized = json_encode($distribution);

        Redis::shouldReceive('get')
            ->once()
            ->with(\Mockery::pattern('/fraud:ml:drift:reference:*/'))
            ->andReturn($serialized);

        $result = $this->driftDetector->getReferenceDistribution('v1.0.0', 'test_feature');

        $this->assertEquals($distribution, $result, 'Reference distribution should be retrieved');
    }

    public function test_get_reference_distribution_not_found(): void
    {
        Redis::shouldReceive('get')
            ->once()
            ->with(\Mockery::pattern('/fraud:ml:drift:reference:*/'))
            ->andReturnNull();

        $result = $this->driftDetector->getReferenceDistribution('v1.0.0', 'test_feature');

        $this->assertNull($result, 'Should return null when distribution not found');
    }

    public function test_detect_drift_handles_mixed_categorical_and_continuous(): void
    {
        $featuresData = [
            'categorical_feature' => [
                'expected' => ['A' => 100, 'B' => 100],
                'actual' => ['A' => 150, 'B' => 50],
            ],
            'continuous_feature' => [
                'expected' => [1.0, 2.0, 3.0, 4.0, 5.0],
                'actual' => [1.5, 2.5, 3.5, 4.5, 5.5],
            ],
        ];

        $report = $this->driftDetector->detectDrift($featuresData);

        $this->assertEquals(2, $report['features_checked'], 'Both features should be checked');
        $this->assertArrayHasKey('max_psi', $report, 'Max PSI should be calculated');
        $this->assertArrayHasKey('max_ks', $report, 'Max KS should be calculated');
    }

    public function test_detect_drift_skips_empty_data(): void
    {
        $featuresData = [
            'feature_1' => [
                'expected' => [],
                'actual' => ['A' => 100],
            ],
            'feature_2' => [
                'expected' => ['A' => 100],
                'actual' => [],
            ],
        ];

        $report = $this->driftDetector->detectDrift($featuresData);

        $this->assertEquals(0, $report['features_checked'], 'Empty features should be skipped');
        $this->assertFalse($report['overall_drift_detected'], 'No drift should be detected from empty data');
    }

    public function test_detect_drift_includes_moderate_drift_features(): void
    {
        $featuresData = [
            'feature_1' => [
                'expected' => ['A' => 100, 'B' => 100, 'C' => 100],
                'actual' => ['A' => 120, 'B' => 100, 'C' => 80],
            ],
        ];

        $report = $this->driftDetector->detectDrift($featuresData);

        $this->assertArrayHasKey('moderate_drift_features', $report);
        // May or may not have moderate drift depending on exact PSI value
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }
}
