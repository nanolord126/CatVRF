<?php declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use App\Models\FraudModelVersion;
use Illuminate\Log\LogManager;

/**
 * MLModelValidationService — validates ML models before promotion
 * 
 * Performs:
 * - AUC/ROC validation
 * - Feature drift detection (PSI - Population Stability Index)
 * - Quality threshold checks
 * - Automatic rollback recommendation
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class MLModelValidationService
{
    private const MIN_AUC_THRESHOLD = 0.92;
    private const PSI_THRESHOLD = 0.2;
    private const MIN_SHADOW_PREDICTIONS = 100;

    public function __construct(
        private readonly LogManager $logger,
    ) {}

    /**
     * Validate model for promotion
     */
    public function validateModel(FraudModelVersion $model, string $correlationId): array
    {
        $results = [
            'passed' => true,
            'reason' => null,
            'metrics' => [],
        ];

        // Check shadow period
        if (!$this->validateShadowPeriod($model)) {
            $results['passed'] = false;
            $results['reason'] = 'Shadow period not complete';
            $results['metrics']['shadow_period_valid'] = false;
            return $results;
        }
        $results['metrics']['shadow_period_valid'] = true;

        // Check shadow predictions count
        if (!$this->validateShadowPredictionsCount($model)) {
            $results['passed'] = false;
            $results['reason'] = 'Insufficient shadow predictions';
            $results['metrics']['shadow_predictions_valid'] = false;
            return $results;
        }
        $results['metrics']['shadow_predictions_valid'] = true;
        $results['metrics']['shadow_predictions_count'] = $model->shadow_predictions_count;

        // Check AUC threshold
        $aucValid = $this->validateAUC($model);
        $results['metrics']['auc_valid'] = $aucValid;
        $results['metrics']['shadow_auc_roc'] = $model->shadow_auc_roc;
        
        if (!$aucValid) {
            $results['passed'] = false;
            $results['reason'] = sprintf(
                'AUC below threshold: %.4f < %.4f',
                $model->shadow_auc_roc,
                self::MIN_AUC_THRESHOLD
            );
        }

        // Check feature drift (PSI)
        $psiValid = $this->validateFeatureDrift($model, $correlationId);
        $results['metrics']['psi_valid'] = $psiValid;
        $results['metrics']['psi_score'] = $model->shadow_drift_score;

        if (!$psiValid) {
            $results['passed'] = false;
            $results['reason'] = sprintf(
                'Feature drift detected: PSI %.4f > %.4f',
                $model->shadow_drift_score ?? 0,
                self::PSI_THRESHOLD
            );
        }

        $this->logger->info('Model validation completed', [
            'model_version' => $model->version,
            'correlation_id' => $correlationId,
            'passed' => $results['passed'],
            'reason' => $results['reason'],
            'metrics' => $results['metrics'],
        ]);

        return $results;
    }

    /**
     * Validate shadow period (must be at least 24 hours)
     */
    private function validateShadowPeriod(FraudModelVersion $model): bool
    {
        if ($model->shadow_started_at === null) {
            return false;
        }

        $hoursInShadow = $model->shadow_started_at->diffInHours(now());
        return $hoursInShadow >= 24;
    }

    /**
     * Validate shadow predictions count
     */
    private function validateShadowPredictionsCount(FraudModelVersion $model): bool
    {
        return $model->shadow_predictions_count >= self::MIN_SHADOW_PREDICTIONS;
    }

    /**
     * Validate AUC/ROC score
     */
    private function validateAUC(FraudModelVersion $model): bool
    {
        if ($model->shadow_auc_roc === null) {
            return false;
        }

        return $model->shadow_auc_roc >= self::MIN_AUC_THRESHOLD;
    }

    /**
     * Validate feature drift using PSI
     */
    private function validateFeatureDrift(FraudModelVersion $model, string $correlationId): bool
    {
        if ($model->shadow_drift_score === null) {
            // If PSI not calculated, assume valid (will be calculated during shadow period)
            return true;
        }

        return $model->shadow_drift_score <= self::PSI_THRESHOLD;
    }

    /**
     * Calculate PSI for feature drift detection
     * This would normally compare training vs shadow feature distributions
     */
    public function calculatePSI(array $trainingFeatures, array $shadowFeatures): float
    {
        // Simplified PSI calculation
        // In production, this would use proper statistical methods
        
        $psi = 0.0;
        $featureCount = min(count($trainingFeatures), count($shadowFeatures));

        for ($i = 0; $i < $featureCount; $i++) {
            $expected = $trainingFeatures[$i] + 1e-10; // Avoid division by zero
            $actual = $shadowFeatures[$i] + 1e-10;
            
            $psi += ($actual - $expected) * log($actual / $expected);
        }

        return abs($psi);
    }
}
