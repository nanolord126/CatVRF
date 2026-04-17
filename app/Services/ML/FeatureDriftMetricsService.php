<?php

declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Feature Drift Metrics Service
 * 
 * Exports drift detection metrics for Prometheus/OpenTelemetry scraping.
 * Provides metrics endpoint for Grafana dashboard integration.
 * 
 * Production-ready for CatVRF ML pipeline with medical compliance.
 */
final readonly class FeatureDriftMetricsService
{
    private const CACHE_KEY_PREFIX = 'fraud:ml:drift:metrics:';
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Export drift metrics in Prometheus format
     * 
     * @return string Prometheus metrics text format
     */
    public function exportPrometheusMetrics(): string
    {
        $metrics = [];
        
        // Get latest drift report from cache
        $latestReport = Cache::get(self::CACHE_KEY_PREFIX . 'latest_report');
        
        if ($latestReport === null) {
            return "# No drift metrics available\n";
        }

        $metrics[] = "# HELP fraud_ml_feature_drift_psi_score PSI score for feature drift detection";
        $metrics[] = "# TYPE fraud_ml_feature_drift_psi_score gauge";
        $metrics[] = sprintf("fraud_ml_feature_drift_psi_score{vertical_code=\"%s\"} %f", 
            $latestReport['vertical_code'] ?? 'unknown', 
            $latestReport['max_psi'] ?? 0
        );

        $metrics[] = "# HELP fraud_ml_feature_drift_ks_score KS statistic for feature drift detection";
        $metrics[] = "# TYPE fraud_ml_feature_drift_ks_score gauge";
        $metrics[] = sprintf("fraud_ml_feature_drift_ks_score{vertical_code=\"%s\"} %f", 
            $latestReport['vertical_code'] ?? 'unknown', 
            $latestReport['max_ks'] ?? 0
        );

        $metrics[] = "# HELP fraud_ml_drifted_features_count Number of features with critical drift";
        $metrics[] = "# TYPE fraud_ml_drifted_features_count gauge";
        $metrics[] = sprintf("fra_ml_drifted_features_count{vertical_code=\"%s\"} %d", 
            $latestReport['vertical_code'] ?? 'unknown', 
            count($latestReport['drifted_features'] ?? [])
        );

        $metrics[] = "# HELP fraud_ml_moderate_drift_features_count Number of features with moderate drift";
        $metrics[] = "# TYPE fraud_ml_moderate_drift_features_count gauge";
        $metrics[] = sprintf("fra_ml_moderate_drift_features_count{vertical_code=\"%s\"} %d", 
            $latestReport['vertical_code'] ?? 'unknown', 
            count($latestReport['moderate_drift_features'] ?? [])
        );

        $metrics[] = "# HELP fraud_ml_overall_drift_detected Overall drift detection status";
        $metrics[] = "# TYPE fraud_ml_overall_drift_detected gauge";
        $metrics[] = sprintf("fra_ml_overall_drift_detected{vertical_code=\"%s\"} %d", 
            $latestReport['vertical_code'] ?? 'unknown', 
            $latestReport['overall_drift_detected'] ? 1 : 0
        );

        $metrics[] = "# HELP fraud_ml_drift_check_timestamp Timestamp of last drift check";
        $metrics[] = "# TYPE fraud_ml_drift_check_timestamp gauge";
        $metrics[] = sprintf("fra_ml_drift_check_timestamp{vertical_code=\"%s\"} %d", 
            $latestReport['vertical_code'] ?? 'unknown', 
            strtotime($latestReport['timestamp'] ?? 'now')
        );

        // Individual feature drift scores
        foreach ($latestReport['drifted_features'] ?? [] as $feature) {
            $metrics[] = sprintf(
                "fra_ml_feature_drift_score{feature=\"%s\",metric=\"%s\",vertical_code=\"%s\",level=\"critical\"} %f",
                $feature['feature'],
                $feature['metric'],
                $latestReport['vertical_code'] ?? 'unknown',
                $feature['score']
            );
        }

        foreach ($latestReport['moderate_drift_features'] ?? [] as $feature) {
            $metrics[] = sprintf(
                "fra_ml_feature_drift_score{feature=\"%s\",metric=\"%s\",vertical_code=\"%s\",level=\"moderate\"} %f",
                $feature['feature'],
                $feature['metric'],
                $latestReport['vertical_code'] ?? 'unknown',
                $feature['score']
            );
        }

        return implode("\n", $metrics) . "\n";
    }

    /**
     * Store drift report for metrics export
     * 
     * @param array $driftReport Drift report from FeatureDriftDetectorService
     */
    public function storeDriftReport(array $driftReport): void
    {
        Cache::put(self::CACHE_KEY_PREFIX . 'latest_report', $driftReport, self::CACHE_TTL);

        $this->logger->debug('Drift report stored for metrics export', [
            'vertical_code' => $driftReport['vertical_code'] ?? 'unknown',
            'max_psi' => $driftReport['max_psi'] ?? 0,
            'max_ks' => $driftReport['max_ks'] ?? 0,
            'overall_drift_detected' => $driftReport['overall_drift_detected'] ?? false,
        ]);
    }

    /**
     * Get metrics for Grafana dashboard (JSON format)
     * 
     * @return array Metrics data
     */
    public function getGrafanaMetrics(): array
    {
        $latestReport = Cache::get(self::CACHE_KEY_PREFIX . 'latest_report');

        if ($latestReport === null) {
            return [
                'status' => 'no_data',
                'message' => 'No drift metrics available',
            ];
        }

        return [
            'status' => 'ok',
            'timestamp' => $latestReport['timestamp'],
            'vertical_code' => $latestReport['vertical_code'] ?? 'unknown',
            'summary' => [
                'features_checked' => $latestReport['features_checked'] ?? 0,
                'drifted_features_count' => count($latestReport['drifted_features'] ?? []),
                'moderate_drift_features_count' => count($latestReport['moderate_drift_features'] ?? []),
                'max_psi' => $latestReport['max_psi'] ?? 0,
                'max_ks' => $latestReport['max_ks'] ?? 0,
                'overall_drift_detected' => $latestReport['overall_drift_detected'] ?? false,
            ],
            'drifted_features' => $latestReport['drifted_features'] ?? [],
            'moderate_drift_features' => $latestReport['moderate_drift_features'] ?? [],
        ];
    }

    /**
     * Record individual feature drift score
     * 
     * @param string $featureName Feature name
     * @param string $metricType Metric type (psi or ks)
     * @param float $score Drift score
     * @param string $verticalCode Vertical code
     */
    public function recordFeatureDrift(string $featureName, string $metricType, float $score, string $verticalCode = 'default'): void
    {
        $key = self::CACHE_KEY_PREFIX . "feature:{$verticalCode}:{$featureName}:{$metricType}";
        
        // Store with histogram-like structure (last 100 values)
        $history = Cache::get($key, []);
        $history[] = [
            'score' => $score,
            'timestamp' => now()->toIso8601String(),
        ];

        // Keep only last 100 values
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        Cache::put($key, $history, self::CACHE_TTL);
    }

    /**
     * Get feature drift history for time-series visualization
     * 
     * @param string $featureName Feature name
     * @param string $metricType Metric type (psi or ks)
     * @param string $verticalCode Vertical code
     * @return array History data
     */
    public function getFeatureDriftHistory(string $featureName, string $metricType, string $verticalCode = 'default'): array
    {
        $key = self::CACHE_KEY_PREFIX . "feature:{$verticalCode}:{$featureName}:{$metricType}";
        
        return Cache::get($key, []);
    }
}
