<?php

declare(strict_types=1);

namespace App\Services\ML\Traits;

use App\Services\ML\FeatureDriftDetectorService;
use App\Services\ML\FeatureDriftMetricsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Feature Drift Detection Trait
 * 
 * Reusable trait for adding drift detection to any vertical's ML service.
 * Provides methods for runtime drift checking and reporting.
 * 
 * Usage in vertical services:
 * ```php
 * use App\Services\ML\Traits\HasFeatureDriftDetection;
 * 
 * final class MedicalAIConstructorService
 * {
 *     use HasFeatureDriftDetection;
 *     
 *     public function __construct(
 *         private readonly FeatureDriftDetectorService $driftDetector,
 *         private readonly FeatureDriftMetricsService $driftMetrics,
 *         private readonly string $verticalCode = 'medical'
 *     ) {
 *         $this->initializeDriftDetection();
 *     }
 * }
 * ```
 */
trait HasFeatureDriftDetection
{
    protected FeatureDriftDetectorService $driftDetector;
    protected FeatureDriftMetricsService $driftMetrics;
    protected string $verticalCode = 'default';
    protected array $monitoredFeatures = [];
    protected bool $driftDetectionEnabled = true;

    /**
     * Initialize drift detection for the vertical
     */
    protected function initializeDriftDetection(): void
    {
        $this->driftDetectionEnabled = Config::get('fraud.drift_detection.enabled', true);
        
        // Load vertical-specific monitored features from config
        $this->monitoredFeatures = Config::get("fraud.drift_detection.monitored_features.{$this->verticalCode}", []);
        
        if (empty($this->monitoredFeatures)) {
            // Use default monitored features if vertical-specific not configured
            $this->monitoredFeatures = Config::get('fraud.monitored_features', []);
        }

        Log::debug('Drift detection initialized', [
            'vertical' => $this->verticalCode,
            'enabled' => $this->driftDetectionEnabled,
            'monitored_features_count' => count($this->monitoredFeatures),
        ]);
    }

    /**
     * Check drift for a single feature value during inference
     * 
     * @param string $featureName Feature name
     * @param mixed $featureValue Current feature value
     * @return array Drift check result
     */
    protected function checkFeatureDrift(string $featureName, mixed $featureValue): array
    {
        if (!$this->driftDetectionEnabled) {
            return [
                'drift_detected' => false,
                'drift_score' => 0.0,
                'reason' => 'disabled',
            ];
        }

        // Get active model version (simplified - in production would query DB)
        $modelVersion = $this->getCurrentModelVersion();
        if ($modelVersion === null) {
            return [
                'drift_detected' => false,
                'drift_score' => 0.0,
                'reason' => 'no_active_model',
            ];
        }

        // Get reference distribution
        $referenceDist = $this->driftDetector->getReferenceDistribution(
            $modelVersion,
            $featureName,
            $this->verticalCode
        );

        if ($referenceDist === null) {
            return [
                'drift_detected' => false,
                'drift_score' => 0.0,
                'reason' => 'no_reference_distribution',
            ];
        }

        // Simple drift check for categorical features
        if ($this->isCategoricalDistribution($referenceDist)) {
            $driftDetected = !array_key_exists((string)$featureValue, $referenceDist);

            if ($driftDetected) {
                Log::warning('Feature drift detected - unknown category', [
                    'vertical' => $this->verticalCode,
                    'feature' => $featureName,
                    'value' => $featureValue,
                    'model_version' => $modelVersion,
                ]);

                $this->driftMetrics->recordFeatureDrift($featureName, 'psi', 1.0, $this->verticalCode);
            }

            return [
                'drift_detected' => $driftDetected,
                'drift_score' => $driftDetected ? 1.0 : 0.0,
                'threshold' => 0.0,
                'reason' => $driftDetected ? 'unknown_category' : 'ok',
            ];
        }

        // For continuous features - simple outlier detection
        $referenceValues = array_keys($referenceDist);
        $mean = array_sum($referenceValues) / count($referenceValues);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $referenceValues)) / count($referenceValues);
        $stdDev = sqrt($variance) ?: 1;

        $zScore = abs(($featureValue - $mean) / $stdDev);
        $driftDetected = $zScore > 3.0;

        if ($driftDetected) {
            Log::warning('Feature drift detected - outlier value', [
                'vertical' => $this->verticalCode,
                'feature' => $featureName,
                'value' => $featureValue,
                'z_score' => $zScore,
                'model_version' => $modelVersion,
            ]);

            $this->driftMetrics->recordFeatureDrift($featureName, 'ks', $zScore / 3.0, $this->verticalCode);
        }

        return [
            'drift_detected' => $driftDetected,
            'drift_score' => $zScore / 3.0,
            'threshold' => 1.0,
            'z_score' => $zScore,
            'mean' => $mean,
            'std_dev' => $stdDev,
            'reason' => $driftDetected ? 'outlier' : 'ok',
        ];
    }

    /**
     * Check drift for multiple features (batch check)
     * 
     * @param array $features ['feature_name' => value]
     * @return array Drift report
     */
    protected function checkMultipleFeaturesDrift(array $features): array
    {
        $driftReport = [
            'vertical_code' => $this->verticalCode,
            'features_checked' => count($features),
            'drifted_features' => [],
            'max_drift_score' => 0.0,
            'overall_drift_detected' => false,
            'timestamp' => now()->toIso8601String(),
        ];

        foreach ($features as $featureName => $featureValue) {
            $result = $this->checkFeatureDrift($featureName, $featureValue);
            
            if ($result['drift_detected']) {
                $driftReport['drifted_features'][] = [
                    'feature' => $featureName,
                    'value' => $featureValue,
                    'drift_score' => $result['drift_score'],
                    'reason' => $result['reason'],
                ];
                $driftReport['max_drift_score'] = max($driftReport['max_drift_score'], $result['drift_score']);
                $driftReport['overall_drift_detected'] = true;
            }
        }

        if ($driftReport['overall_drift_detected']) {
            Log::warning('Multiple features drift detected', [
                'vertical' => $this->verticalCode,
                'drifted_features_count' => count($driftReport['drifted_features']),
                'max_drift_score' => $driftReport['max_drift_score'],
            ]);
        }

        return $driftReport;
    }

    /**
     * Store reference distribution for a feature
     * 
     * @param string $modelVersion Model version
     * @param string $featureName Feature name
     * @param array $distribution Reference distribution
     * @return bool Success status
     */
    protected function storeReferenceDistribution(
        string $modelVersion,
        string $featureName,
        array $distribution
    ): bool {
        return $this->driftDetector->storeReferenceDistribution(
            $modelVersion,
            $featureName,
            $distribution,
            $this->verticalCode
        );
    }

    /**
     * Get current model version for the vertical
     * Override in vertical service if needed
     * 
     * @return string|null Model version
     */
    protected function getCurrentModelVersion(): ?string
    {
        // Default implementation - override in vertical services
        return cache("{$this->verticalCode}_model_active_version");
    }

    /**
     * Check if distribution is categorical
     * 
     * @param array $distribution Distribution
     * @return bool
     */
    private function isCategoricalDistribution(array $distribution): bool
    {
        $keys = array_keys($distribution);
        
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                return true;
            }
        }

        return count($keys) < 20;
    }

    /**
     * Get drift thresholds for the vertical
     * 
     * @return array Thresholds
     */
    protected function getDriftThresholds(): array
    {
        return Config::get("fraud.drift_thresholds.{$this->verticalCode}", [
            'psi_critical' => 0.25,
            'psi_moderate' => 0.1,
            'ks_critical' => 0.1,
            'ks_moderate' => 0.05,
        ]);
    }

    /**
     * Enable/disable drift detection at runtime
     * 
     * @param bool $enabled
     */
    public function setDriftDetectionEnabled(bool $enabled): void
    {
        $this->driftDetectionEnabled = $enabled;
        
        Log::info('Drift detection toggled', [
            'vertical' => $this->verticalCode,
            'enabled' => $enabled,
        ]);
    }

    /**
     * Check if drift detection is enabled
     * 
     * @return bool
     */
    public function isDriftDetectionEnabled(): bool
    {
        return $this->driftDetectionEnabled;
    }
}
