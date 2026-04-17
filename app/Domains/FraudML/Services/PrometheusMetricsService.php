<?php declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use Spatie\Prometheus\Facades\Prometheus;
use Illuminate\Support\Str;

/**
 * PrometheusMetricsService — Production-ready ML model metrics for Prometheus
 * 
 * Uses Spatie Laravel Prometheus with Redis storage backend.
 * All metrics follow Prometheus best practices with low-cardinality labels.
 * 
 * Metrics exported:
 * - ml_retrain_duration_seconds (Histogram)
 * - ml_retrain_success_total (Counter)
 * - ml_model_auc_current (Gauge)
 * - ml_model_promoted_timestamp (Gauge)
 * - ml_model_version_updated_total (Counter)
 * - ml_feature_drift_score (Gauge)
 * - ml_feature_drift_psi (Gauge)
 * - ml_feature_drift_ks (Gauge)
 * - ml_feature_drift_js (Gauge)
 * - ml_feature_drift_combined (Gauge)
 * - ml_feature_drift_detected_total (Counter)
 * - ml_vertical_drift_score (Gauge)
 * - ml_vertical_drift_detected_total (Counter)
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class PrometheusMetricsService
{
    private const NAMESPACE = 'catvrf';

    public function __construct(
        private readonly LogManager $logger,
    ) {}

    /**
     * Record retrain duration (Histogram)
     */
    public function recordRetrainDuration(float $duration, string $correlationId): void
    {
        Prometheus::addHistogram()
            ->name(self::NAMESPACE . '_ml_retrain_duration_seconds')
            ->help('ML model retrain duration in seconds')
            ->label('status', 'completed')
            ->observe($duration);

        $this->logger->debug('ML retrain duration recorded', [
            'duration' => $duration,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record retrain success (Counter)
     */
    public function recordRetrainSuccess(string $status, string $correlationId): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_ml_retrain_success_total')
            ->help('ML model retrain success count')
            ->label('status', $status)
            ->inc();
    }

    /**
     * Record current model AUC (Gauge)
     */
    public function recordModelAUC(float $auc, string $modelVersion, string $correlationId): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_ml_model_auc_current')
            ->help('Current ML model AUC score')
            ->label('model_version', $this->sanitizeLabel($modelVersion))
            ->set($auc);
    }

    /**
     * Record model promotion timestamp (Gauge)
     */
    public function recordModelPromotion(string $modelVersion, string $correlationId): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_ml_model_promoted_timestamp')
            ->help('ML model promotion timestamp')
            ->label('model_version', $this->sanitizeLabel($modelVersion))
            ->set(now()->timestamp);
    }

    /**
     * Record model version update (Counter)
     */
    public function recordModelVersionUpdate(string $action, string $modelType, string $correlationId): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_ml_model_version_updated_total')
            ->help('ML model version update count')
            ->label('action', $action)
            ->label('model_type', $modelType)
            ->inc();
    }

    /**
     * Record tenants processed count (Counter)
     */
    public function recordTenantsProcessed(int $count, string $correlationId): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_ml_retrain_tenants_processed_total')
            ->help('Total tenants processed during ML retrain')
            ->incBy($count);
    }

    /**
     * Record model training metrics (Gauge)
     */
    public function recordTrainingMetrics(array $metrics, string $modelVersion, string $correlationId): void
    {
        foreach ($metrics as $metricName => $value) {
            if (is_numeric($value)) {
                Prometheus::addGauge()
                    ->name(self::NAMESPACE . '_ml_model_training_metric')
                    ->help('ML model training metrics')
                    ->label('metric_name', $this->sanitizeLabel($metricName))
                    ->label('model_version', $this->sanitizeLabel($modelVersion))
                    ->set((float) $value);
            }
        }
    }

    /**
     * Record feature drift PSI score (Gauge)
     */
    public function recordFeatureDriftPSI(float $psiScore, string $featureName, string $vertical, string $correlationId): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_feature_drift_psi')
            ->help('Feature drift PSI score')
            ->label('feature', $this->sanitizeLabel($featureName))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->set($psiScore);
    }

    /**
     * Record feature drift KS statistic (Gauge)
     */
    public function recordFeatureDriftKS(float $ksStatistic, string $featureName, string $vertical, string $correlationId): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_feature_drift_ks')
            ->help('Feature drift KS statistic')
            ->label('feature', $this->sanitizeLabel($featureName))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->set($ksStatistic);
    }

    /**
     * Record feature drift JS divergence (Gauge)
     */
    public function recordFeatureDriftJS(float $jsDivergence, string $featureName, string $vertical, string $correlationId): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_feature_drift_js')
            ->help('Feature drift JS divergence')
            ->label('feature', $this->sanitizeLabel($featureName))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->set($jsDivergence);
    }

    /**
     * Record feature drift combined score (Gauge)
     */
    public function recordFeatureDriftCombined(float $combinedScore, string $featureName, string $vertical, string $correlationId): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_feature_drift_combined')
            ->help('Feature drift combined score')
            ->label('feature', $this->sanitizeLabel($featureName))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->set($combinedScore);
    }

    /**
     * Record feature drift detected event (Counter)
     */
    public function recordFeatureDriftDetected(string $featureName, string $vertical, string $severity, string $correlationId): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_feature_drift_detected_total')
            ->help('Feature drift detected count')
            ->label('feature', $this->sanitizeLabel($featureName))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->label('severity', $severity)
            ->inc();
    }

    /**
     * Record vertical drift score (Gauge)
     */
    public function recordVerticalDriftScore(float $driftScore, string $vertical, string $correlationId): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_vertical_drift_score')
            ->help('Vertical drift score')
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->set($driftScore);
    }

    /**
     * Record vertical drift detected event (Counter)
     */
    public function recordVerticalDriftDetected(string $vertical, string $correlationId): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_vertical_drift_detected_total')
            ->help('Vertical drift detected count')
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->inc();
    }

    /**
     * Record quota usage ratio (Gauge)
     */
    public function recordQuotaUsageRatio(float $ratio, string $resourceType, string $vertical, string $correlationId): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_quota_usage_ratio')
            ->help('Quota usage ratio')
            ->label('resource_type', $this->sanitizeLabel($resourceType))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->set($ratio);
    }

    /**
     * Record quota exceeded event (Counter)
     */
    public function recordQuotaExceeded(string $resourceType, string $vertical, string $correlationId): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_quota_exceeded_total')
            ->help('Quota exceeded count')
            ->label('resource_type', $this->sanitizeLabel($resourceType))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->inc();
    }

    /**
     * Record AI tokens consumed (Counter)
     */
    public function recordAITokensConsumed(int $tokens, string $model, string $vertical, string $correlationId): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_ai_tokens_consumed_total')
            ->help('AI tokens consumed count')
            ->label('model', $this->sanitizeLabel($model))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->incBy($tokens);
    }

    /**
     * Record fraud ML inference latency (Histogram)
     */
    public function recordFraudMLInferenceLatency(float $latency, string $modelVersion, string $correlationId): void
    {
        Prometheus::addHistogram()
            ->name(self::NAMESPACE . '_fraud_ml_inference_latency_seconds')
            ->help('Fraud ML inference latency in seconds')
            ->label('model_version', $this->sanitizeLabel($modelVersion))
            ->observe($latency);
    }

    /**
     * Record fraud score (Histogram)
     */
    public function recordFraudScore(float $score, string $vertical, string $correlationId): void
    {
        Prometheus::addHistogram()
            ->name(self::NAMESPACE . '_fraud_score_distribution')
            ->help('Fraud score distribution')
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->observe($score);
    }

    /**
     * Record fraud blocked by ML (Counter)
     */
    public function recordFraudBlockedByML(string $reason, string $vertical, string $correlationId): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_fraud_blocked_by_ml_total')
            ->help('Fraud blocked by ML count')
            ->label('reason', $this->sanitizeLabel($reason))
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->inc();
    }

    /**
     * Sanitize label value for Prometheus
     * Prevents high cardinality and invalid characters
     */
    private function sanitizeLabel(string $value): string
    {
        // Remove or replace invalid characters
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', $value);
        
        // Limit length to prevent cardinality issues
        return substr($sanitized, 0, 50);
    }
}
