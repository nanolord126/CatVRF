<?php declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;

/**
 * PrometheusMetricsService — ML model metrics for Prometheus
 * 
 * In production, this would use prometheus_client_php library.
 * For now, we log metrics that can be scraped by a log-based exporter.
 * 
 * Metrics exported:
 * - ml_retrain_duration_seconds
 * - ml_model_auc_current
 * - ml_model_promoted_timestamp
 * - ml_model_version_updated_total
 * - ml_feature_drift_score
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class PrometheusMetricsService
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}

    /**
     * Record retrain duration
     */
    public function recordRetrainDuration(float $duration, string $correlationId): void
    {
        $this->logMetric('ml_retrain_duration_seconds', $duration, [
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record current model AUC
     */
    public function recordModelAUC(float $auc, string $modelVersion, string $correlationId): void
    {
        $this->logMetric('ml_model_auc_current', $auc, [
            'model_version' => $modelVersion,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record model promotion timestamp
     */
    public function recordModelPromotion(string $modelVersion, string $correlationId): void
    {
        $this->logMetric('ml_model_promoted_timestamp', now()->timestamp, [
            'model_version' => $modelVersion,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record model version update
     */
    public function recordModelVersionUpdate(string $action, string $modelType, string $correlationId): void
    {
        $this->logMetric('ml_model_version_updated_total', 1, [
            'action' => $action,
            'model_type' => $modelType,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record feature drift score
     */
    public function recordFeatureDrift(float $psiScore, string $modelVersion, string $correlationId): void
    {
        $this->logMetric('ml_feature_drift_score', $psiScore, [
            'model_version' => $modelVersion,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record tenants processed count
     */
    public function recordTenantsProcessed(int $count, string $correlationId): void
    {
        $this->logMetric('ml_retrain_tenants_processed', $count, [
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record model training metrics
     */
    public function recordTrainingMetrics(array $metrics, string $modelVersion, string $correlationId): void
    {
        foreach ($metrics as $metricName => $value) {
            if (is_numeric($value)) {
                $this->logMetric('ml_model_training_metric', $value, [
                    'metric_name' => $metricName,
                    'model_version' => $modelVersion,
                    'correlation_id' => $correlationId,
                ]);
            }
        }
    }

    /**
     * Record feature drift PSI score
     */
    public function recordFeatureDriftPSI(float $psiScore, string $featureName, string $vertical, string $correlationId): void
    {
        $this->logMetric('ml_feature_drift_psi', $psiScore, [
            'feature' => $featureName,
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record feature drift KS statistic
     */
    public function recordFeatureDriftKS(float $ksStatistic, string $featureName, string $vertical, string $correlationId): void
    {
        $this->logMetric('ml_feature_drift_ks', $ksStatistic, [
            'feature' => $featureName,
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record feature drift JS divergence
     */
    public function recordFeatureDriftJS(float $jsDivergence, string $featureName, string $vertical, string $correlationId): void
    {
        $this->logMetric('ml_feature_drift_js', $jsDivergence, [
            'feature' => $featureName,
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record feature drift combined score
     */
    public function recordFeatureDriftCombined(float $combinedScore, string $featureName, string $vertical, string $correlationId): void
    {
        $this->logMetric('ml_feature_drift_combined', $combinedScore, [
            'feature' => $featureName,
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record feature drift detected event
     */
    public function recordFeatureDriftDetected(string $featureName, string $vertical, string $severity, string $correlationId): void
    {
        $this->logMetric('ml_feature_drift_detected_total', 1, [
            'feature' => $featureName,
            'vertical' => $vertical,
            'severity' => $severity,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record vertical drift score
     */
    public function recordVerticalDriftScore(float $driftScore, string $vertical, string $correlationId): void
    {
        $this->logMetric('ml_vertical_drift_score', $driftScore, [
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Record vertical drift detected event
     */
    public function recordVerticalDriftDetected(string $vertical, string $correlationId): void
    {
        $this->logMetric('ml_vertical_drift_detected_total', 1, [
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Log metric in Prometheus format
     */
    private function logMetric(string $name, float $value, array $labels = []): void
    {
        $labelString = '';
        if (!empty($labels)) {
            $labelPairs = [];
            foreach ($labels as $key => $val) {
                $labelPairs[] = sprintf('%s="%s"', $key, $val);
            }
            $labelString = '{' . implode(',', $labelPairs) . '}';
        }

        $metricLine = sprintf('%s%s %f', $name, $labelString, $value);

        Log::channel('audit')->info('PROMETHEUS_METRIC', [
            'metric' => $metricLine,
        ]);
    }
}
