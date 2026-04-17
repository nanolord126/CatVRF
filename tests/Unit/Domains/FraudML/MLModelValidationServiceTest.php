<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use App\Domains\FraudML\Services\MLModelValidationService;
use App\Models\FraudModelVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MLModelValidationServiceTest — unit tests for ML model validation service
 * 
 * @covers \App\Domains\FraudML\Services\MLModelValidationService
 */
final class MLModelValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private MLModelValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MLModelValidationService::class);
    }

    public function test_validate_shadow_period_requires_minimum_24_hours(): void
    {
        $model = FraudModelVersion::create([
            'version' => '2026-04-17-v1',
            'model_type' => 'lightgbm',
            'shadow_started_at' => now()->subHours(12), // Only 12 hours
            'is_shadow' => true,
            'shadow_predictions_count' => 150,
            'shadow_auc_roc' => 0.95,
        ]);

        $result = $this->service->validateModel($model, 'test-correlation-1');

        $this->assertFalse($result['passed']);
        $this->assertEquals('Shadow period not complete', $result['reason']);
    }

    public function test_validate_shadow_predictions_requires_minimum_count(): void
    {
        $model = FraudModelVersion::create([
            'version' => '2026-04-17-v2',
            'model_type' => 'lightgbm',
            'shadow_started_at' => now()->subHours(25),
            'is_shadow' => true,
            'shadow_predictions_count' => 50, // Below minimum of 100
            'shadow_auc_roc' => 0.95,
        ]);

        $result = $this->service->validateModel($model, 'test-correlation-2');

        $this->assertFalse($result['passed']);
        $this->assertEquals('Insufficient shadow predictions', $result['reason']);
    }

    public function test_validate_auc_requires_minimum_threshold(): void
    {
        $model = FraudModelVersion::create([
            'version' => '2026-04-17-v3',
            'model_type' => 'lightgbm',
            'shadow_started_at' => now()->subHours(25),
            'is_shadow' => true,
            'shadow_predictions_count' => 150,
            'shadow_auc_roc' => 0.85, // Below minimum of 0.92
        ]);

        $result = $this->service->validateModel($model, 'test-correlation-3');

        $this->assertFalse($result['passed']);
        $this->assertStringContainsString('AUC below threshold', $result['reason']);
    }

    public function test_validate_psi_rejects_high_drift(): void
    {
        $model = FraudModelVersion::create([
            'version' => '2026-04-17-v4',
            'model_type' => 'lightgbm',
            'shadow_started_at' => now()->subHours(25),
            'is_shadow' => true,
            'shadow_predictions_count' => 150,
            'shadow_auc_roc' => 0.95,
            'shadow_drift_score' => 0.3, // Above threshold of 0.2
        ]);

        $result = $this->service->validateModel($model, 'test-correlation-4');

        $this->assertFalse($result['passed']);
        $this->assertStringContainsString('Feature drift detected', $result['reason']);
    }

    public function test_validate_passes_when_all_criteria_met(): void
    {
        $model = FraudModelVersion::create([
            'version' => '2026-04-17-v5',
            'model_type' => 'lightgbm',
            'shadow_started_at' => now()->subHours(25),
            'is_shadow' => true,
            'shadow_predictions_count' => 150,
            'shadow_auc_roc' => 0.95,
            'shadow_drift_score' => 0.1, // Below threshold
        ]);

        $result = $this->service->validateModel($model, 'test-correlation-5');

        $this->assertTrue($result['passed']);
        $this->assertNull($result['reason']);
        $this->assertTrue($result['metrics']['shadow_period_valid']);
        $this->assertTrue($result['metrics']['shadow_predictions_valid']);
        $this->assertTrue($result['metrics']['auc_valid']);
        $this->assertTrue($result['metrics']['psi_valid']);
    }

    public function test_calculate_psi_returns_drift_score(): void
    {
        $trainingFeatures = [0.1, 0.2, 0.3, 0.4, 0.5];
        $shadowFeatures = [0.12, 0.22, 0.28, 0.42, 0.48]; // Slight drift

        $psi = $this->service->calculatePSI($trainingFeatures, $shadowFeatures);

        $this->assertIsFloat($psi);
        $this->assertGreaterThan(0, $psi);
        $this->assertLessThan(1.0, $psi); // Should be relatively low for small drift
    }

    public function test_calculate_psi_returns_zero_for_identical_distributions(): void
    {
        $features = [0.1, 0.2, 0.3, 0.4, 0.5];

        $psi = $this->service->calculatePSI($features, $features);

        // Should be very close to zero (floating point precision may cause small values)
        $this->assertLessThan(0.001, $psi);
    }
}
