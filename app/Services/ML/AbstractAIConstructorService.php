<?php

declare(strict_types=1);

namespace App\Services\ML;

use App\Services\ML\Traits\HasFeatureDriftDetection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Abstract AI Constructor Service with Feature Drift Detection
 * 
 * Base class for all vertical AI Constructor Services with built-in drift detection.
 * Extends HasFeatureDriftDetection trait and provides common AI service functionality.
 * 
 * Usage:
 * ```php
 * final class MedicalAIConstructorService extends AbstractAIConstructorService
 * {
 *     protected string $verticalCode = 'medical';
 *     
 *     public function __construct(
 *         FeatureDriftDetectorService $driftDetector,
 *         FeatureDriftMetricsService $driftMetrics
 *     ) {
 *         parent::__construct($driftDetector, $driftMetrics);
 *     }
 *     
 *     public function generateAIResponse(string $prompt): array
 *     {
 *         // Check drift for key features
 *         $this->checkFeatureDrift('prompt_length', strlen($prompt));
 *         
 *         // Your AI logic here
 *         return [];
 *     }
 * }
 * ```
 */
abstract class AbstractAIConstructorService
{
    use HasFeatureDriftDetection;

    protected FeatureDriftDetectorService $driftDetector;
    protected FeatureDriftMetricsService $driftMetrics;
    protected string $verticalCode = 'default';
    protected bool $driftDetectionEnabled = true;

    /**
     * Constructor
     * 
     * @param FeatureDriftDetectorService $driftDetector
     * @param FeatureDriftMetricsService $driftMetrics
     */
    public function __construct(
        FeatureDriftDetectorService $driftDetector,
        FeatureDriftMetricsService $driftMetrics
    ) {
        $this->driftDetector = $driftDetector;
        $this->driftMetrics = $driftMetrics;
        $this->driftDetectionEnabled = Config::get('fraud.drift_detection.enabled', true);
        
        $this->initializeDriftDetection();
        
        Log::info('AI Constructor Service initialized with drift detection', [
            'vertical' => $this->verticalCode,
            'drift_detection_enabled' => $this->driftDetectionEnabled,
        ]);
    }

    /**
     * Get vertical-specific monitored features
     * Override in child classes to define custom features
     * 
     * @return array
     */
    protected function getMonitoredFeatures(): array
    {
        return Config::get("fraud.drift_detection.monitored_features.{$this->verticalCode}", []);
    }

    /**
     * Store reference distribution for model training
     * Call this after model training to establish baseline
     * 
     * @param string $modelVersion
     * @param array $features ['feature_name' => [values]]
     * @return void
     */
    public function storeReferenceDistributions(string $modelVersion, array $features): void
    {
        foreach ($features as $featureName => $values) {
            $distribution = $this->calculateDistribution($values);
            $this->storeReferenceDistribution($modelVersion, $featureName, $distribution);
            
            Log::debug('Reference distribution stored', [
                'vertical' => $this->verticalCode,
                'model_version' => $modelVersion,
                'feature' => $featureName,
                'samples_count' => count($values),
            ]);
        }
    }

    /**
     * Calculate distribution from values (histogram)
     * 
     * @param array $values
     * @param int $bins Number of bins
     * @return array
     */
    protected function calculateDistribution(array $values, int $bins = 10): array
    {
        if (empty($values)) {
            return [];
        }

        sort($values);
        $min = $values[0];
        $max = $values[count($values) - 1];
        
        if ($min === $max) {
            return [$min => count($values)];
        }

        $binWidth = ($max - $min) / $bins;
        $distribution = [];

        foreach ($values as $value) {
            $binIndex = min((int) floor(($value - $min) / $binWidth), $bins - 1);
            $binValue = round($min + $binIndex * $binWidth, 2);
            $distribution[$binValue] = ($distribution[$binValue] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * Log drift detection results for monitoring
     * 
     * @param array $driftReport
     * @return void
     */
    protected function logDriftResults(array $driftReport): void
    {
        if ($driftReport['overall_drift_detected']) {
            Log::warning('Feature drift detected in AI service', [
                'vertical' => $this->verticalCode,
                'drifted_features_count' => count($driftReport['drifted_features']),
                'max_drift_score' => $driftReport['max_drift_score'] ?? 0,
            ]);
        }
    }

    /**
     * Get current model version for the vertical
     * Override in child classes if needed
     * 
     * @return string|null
     */
    protected function getCurrentModelVersion(): ?string
    {
        return cache("{$this->verticalCode}_model_active_version");
    }

    /**
     * Set current model version
     * 
     * @param string $version
     * @return void
     */
    protected function setCurrentModelVersion(string $version): void
    {
        cache(["{$this->verticalCode}_model_active_version" => $version], now()->addHours(24));
        
        Log::info('Model version updated', [
            'vertical' => $this->verticalCode,
            'version' => $version,
        ]);
    }

    /**
     * Check if service should use shadow mode due to drift
     * 
     * @return bool
     */
    protected function shouldUseShadowMode(): bool
    {
        $driftReport = cache("{$this->verticalCode}_drift_report");
        
        if ($driftReport === null) {
            return false;
        }

        return $driftReport['overall_drift_detected'] ?? false;
    }

    /**
     * Get vertical code
     * 
     * @return string
     */
    public function getVerticalCode(): string
    {
        return $this->verticalCode;
    }
}
