<?php

declare(strict_types=1);

namespace App\Services\ML;

use Psr\Log\LoggerInterface;

use App\Services\ML\Traits\HasFeatureDriftDetection;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

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
 *     protected readonly string $verticalCode = 'medical';
 *
 *     public function __construct(private readonly LoggerInterface $logger,
        *         FeatureDriftDetectorService $driftDetector,
 *         FeatureDriftMetricsService $driftMetrics
 *) {
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

    protected readonly FeatureDriftDetectorService $driftDetector;

    protected readonly FeatureDriftMetricsService $driftMetrics;

    protected readonly string $verticalCode = 'default';

    protected readonly bool $driftDetectionEnabled = true;

    /**
     * Constructor
     */
    public function __construct(
        FeatureDriftDetectorService $driftDetector,
        FeatureDriftMetricsService $driftMetrics,
        protected readonly ConfigRepository $config,
        protected readonly LogManager $log,
    ) {
        $this->driftDetector = $driftDetector;
        $this->driftMetrics = $driftMetrics;
        $this->driftDetectionEnabled = $this->config->get('fraud.drift_detection.enabled', true);

        $this->initializeDriftDetection();

        $this->log->$this->logger->info('AI Constructor Service initialized with drift detection', [
            'vertical' => $this->verticalCode,
            'drift_detection_enabled' => $this->driftDetectionEnabled,
        ]);
    }

    /**
     * Store reference distribution for model training
     * Call this after model training to establish baseline
     *
     * @param  array  $features  ['feature_name' => [values]]
     */
    public function storeReferenceDistributions(string $modelVersion, array $features): void
    {
        foreach ($features as $featureName => $values) {
            $distribution = $this->calculateDistribution($values);
            $this->storeReferenceDistribution($modelVersion, $featureName, $distribution);

            $this->log->debug('Reference distribution stored', [
                'vertical' => $this->verticalCode,
                'model_version' => $modelVersion,
                'feature' => $featureName,
                'samples_count' => count($values),
            ]);
        }
    }

    /**
     * Get vertical code
     */
    public function getVerticalCode(): string
    {
        return $this->verticalCode;
    }

    /**
     * Get vertical-specific monitored features
     * Override in child classes to define custom features
     */
    protected function getMonitoredFeatures(): array
    {
        return $this->config->get("fraud.drift_detection.monitored_features.{$this->verticalCode}", []);
    }

    /**
     * Calculate distribution from values (histogram)
     *
     * @param  int  $bins  Number of bins
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
     */
    protected function logDriftResults(array $driftReport): void
    {
        if ($driftReport['overall_drift_detected']) {
            $this->log->warning('Feature drift detected in AI service', [
                'vertical' => $this->verticalCode,
                'drifted_features_count' => count($driftReport['drifted_features']),
                'max_drift_score' => $driftReport['max_drift_score'] ?? 0,
            ]);
        }
    }

    /**
     * Get current model version for the vertical
     * Override in child classes if needed
     */
    protected function getCurrentModelVersion(): ?string
    {
        return cache("{$this->verticalCode}_model_active_version");
    }

    /**
     * Set current model version
     */
    protected function setCurrentModelVersion(string $version): void
    {
        cache(["{$this->verticalCode}_model_active_version" => $version], CarbonImmutable::now()->addHours(24));

        $this->log->$this->logger->info('Model version updated', [
            'vertical' => $this->verticalCode,
            'version' => $version,
        ]);
    }

    /**
     * Check if service should use shadow mode due to drift
     */
    protected function shouldUseShadowMode(): bool
    {
        $driftReport = cache("{$this->verticalCode}_drift_report");

        if ($driftReport === null) {
            return false;
        }

        return $driftReport['overall_drift_detected'] ?? false;
    }
}
