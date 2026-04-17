<?php declare(strict_types=1);

namespace Tests\Unit\Services\ML;

use Tests\TestCase;
use App\Services\ML\FraudMLModelValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

final class FraudMLModelValidatorTest extends TestCase
{
    use RefreshDatabase;

    private FraudMLModelValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = app(FraudMLModelValidator::class);
    }

    public function test_validate_model_with_good_metrics(): void
    {
        $trainingFeatures = [
            ['label' => 0, 'prediction' => 0.1, 'amount_log' => 5.0],
            ['label' => 0, 'prediction' => 0.2, 'amount_log' => 5.5],
            ['label' => 1, 'prediction' => 0.9, 'amount_log' => 8.0],
            ['label' => 1, 'prediction' => 0.85, 'amount_log' => 7.5],
        ];

        $validationFeatures = [
            ['label' => 0, 'prediction' => 0.15, 'amount_log' => 5.2],
            ['label' => 0, 'prediction' => 0.25, 'amount_log' => 5.8],
            ['label' => 1, 'prediction' => 0.88, 'amount_log' => 7.8],
            ['label' => 1, 'prediction' => 0.82, 'amount_log' => 7.3],
        ];

        $result = $this->validator->validateModel(
            $trainingFeatures,
            $validationFeatures,
            'test-v1'
        );

        $this->assertArrayHasKey('auc_roc', $result);
        $this->assertArrayHasKey('psi_score', $result);
        $this->assertArrayHasKey('ks_score', $result);
        $this->assertArrayHasKey('passes_validation', $result);
        $this->assertArrayHasKey('thresholds', $result);
        $this->assertIsFloat($result['auc_roc']);
        $this->assertIsFloat($result['psi_score']);
        $this->assertIsFloat($result['ks_score']);
    }

    public function test_validate_model_fails_with_low_auc(): void
    {
        $trainingFeatures = [
            ['label' => 0, 'prediction' => 0.5, 'amount_log' => 5.0],
            ['label' => 1, 'prediction' => 0.5, 'amount_log' => 5.5],
        ];

        $validationFeatures = [
            ['label' => 0, 'prediction' => 0.5, 'amount_log' => 5.2],
            ['label' => 1, 'prediction' => 0.5, 'amount_log' => 5.8],
        ];

        $result = $this->validator->validateModel(
            $trainingFeatures,
            $validationFeatures,
            'test-v1'
        );

        // AUC should be ~0.5 (random guess), which is below 0.92 threshold
        $this->assertLessThan(0.92, $result['auc_roc']);
        $this->assertFalse($result['passes_validation']);
    }

    public function test_validate_model_fails_with_high_psi(): void
    {
        // Create training data with low values
        $trainingFeatures = array_fill(0, 10, ['label' => 0, 'prediction' => 0.1, 'amount_log' => 1.0]);

        // Create validation data with high values (distribution shift)
        $validationFeatures = array_fill(0, 10, ['label' => 0, 'prediction' => 0.1, 'amount_log' => 10.0]);

        $result = $this->validator->validateModel(
            $trainingFeatures,
            $validationFeatures,
            'test-v1'
        );

        // PSI should be high due to distribution shift
        $this->assertGreaterThan(0.25, $result['psi_score']);
        $this->assertFalse($result['passes_validation']);
    }

    public function test_validate_model_with_empty_data(): void
    {
        $result = $this->validator->validateModel([], [], 'test-v1');

        $this->assertEquals(0.5, $result['auc_roc']); // Random guess baseline
        $this->assertEquals(0.0, $result['psi_score']);
        $this->assertEquals(0.0, $result['ks_score']);
    }

    public function test_rollback_if_failed_with_passing_model(): void
    {
        $validationResult = [
            'auc_roc' => 0.95,
            'psi_score' => 0.1,
            'ks_score' => 0.05,
            'passes_validation' => true,
        ];

        $rolledBack = $this->validator->rollbackIfFailed('test-v1', $validationResult);

        $this->assertFalse($rolledBack);
    }

    public function test_rollback_if_failed_with_failing_model(): void
    {
        $validationResult = [
            'auc_roc' => 0.85,
            'psi_score' => 0.3,
            'ks_score' => 0.15,
            'passes_validation' => false,
        ];

        $rolledBack = $this->validator->rollbackIfFailed('test-v1', $validationResult);

        $this->assertTrue($rolledBack);
    }

    public function test_thresholds_are_correct(): void
    {
        $trainingFeatures = [['label' => 0, 'prediction' => 0.1, 'amount_log' => 5.0]];
        $validationFeatures = [['label' => 0, 'prediction' => 0.1, 'amount_log' => 5.0]];

        $result = $this->validator->validateModel(
            $trainingFeatures,
            $validationFeatures,
            'test-v1'
        );

        $this->assertEquals(0.92, $result['thresholds']['auc_min']);
        $this->assertEquals(0.25, $result['thresholds']['psi_max']);
        $this->assertEquals(0.1, $result['thresholds']['ks_max']);
    }

    public function test_validation_timestamp_is_set(): void
    {
        $trainingFeatures = [['label' => 0, 'prediction' => 0.1, 'amount_log' => 5.0]];
        $validationFeatures = [['label' => 0, 'prediction' => 0.1, 'amount_log' => 5.0]];

        $result = $this->validator->validateModel(
            $trainingFeatures,
            $validationFeatures,
            'test-v1'
        );

        $this->assertArrayHasKey('validation_timestamp', $result);
        $this->assertNotEmpty($result['validation_timestamp']);
    }
}
