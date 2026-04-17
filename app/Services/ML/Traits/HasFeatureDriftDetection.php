<?php

declare(strict_types=1);

namespace App\Services\ML\Traits;

use App\Domains\FraudML\Events\SignificantFeatureDriftDetected;
use App\Domains\FraudML\Services\FeatureDriftDetectorService;
use App\Domains\FraudML\Services\PrometheusMetricsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * HasFeatureDriftDetection — Trait для автоматического detection feature drift
 * 
 * Используется в AI сервисах всех вертикалей для автоматического мониторинга
 * дрифта критичных фич. Интегрируется через use в AI сервисах.
 * 
 * Пример использования:
 * ```php
 * use App\Services\ML\Traits\HasFeatureDriftDetection;
 * 
 * final class MedicalAIConstructorService
 * {
 *     use HasFeatureDriftDetection;
 *     
 *     public function __construct(
 *         private readonly FeatureDriftDetectorService $driftDetector,
 *         private readonly string $verticalCode = 'medical'
 *     ) {
 *         $this->initializeDriftDetection();
 *     }
 * }
 * ```
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
trait HasFeatureDriftDetection
{
    protected ?FeatureDriftDetectorService $driftDetector = null;
    protected ?PrometheusMetricsService $prometheus = null;
    protected string $verticalCode = 'default';
    protected array $monitoredFeatures = [];
    protected bool $driftDetectionEnabled = true;

    /**
     * Initialize drift detection for the vertical
     */
    protected function initializeDriftDetection(): void
    {
        $this->driftDetectionEnabled = Config::get('feature_drift.enabled', true);
        
        // Load vertical-specific monitored features from config
        $config = Config::get("feature_drift.verticals.{$this->verticalCode}", []);
        $this->monitoredFeatures = $config['features'] ?? [];
        
        if (empty($this->monitoredFeatures)) {
            // Use default monitored features if vertical-specific not configured
            $this->monitoredFeatures = Config::get('feature_drift.default_features', [
                'frequency',
                'value_avg',
                'duration_avg',
            ]);
        }

        Log::debug('Drift detection initialized', [
            'vertical' => $this->verticalCode,
            'enabled' => $this->driftDetectionEnabled,
            'monitored_features_count' => count($this->monitoredFeatures),
        ]);
    }

    /**
     * Check drift for all features in batch
     * 
     * @param array $featuresData ['feature_name' => ['expected' => [], 'actual' => []]]
     * @return array Drift detection result
     */
    protected function checkAllFeaturesDrift(array $featuresData): array
    {
        if (!$this->driftDetectionEnabled) {
            return [
                'vertical_code' => $this->verticalCode,
                'overall_drift_detected' => false,
                'reason' => 'disabled',
            ];
        }

        if ($this->driftDetector === null) {
            $this->driftDetector = app(FeatureDriftDetectorService::class);
        }

        $correlationId = Uuid::uuid4()->toString();

        try {
            $result = $this->driftDetector->detectAllFeatures($this->verticalCode, $featuresData);

            // Dispatch event if significant drift detected
            if ($result['summary']['overall_drift_detected'] ?? false) {
                event(new SignificantFeatureDriftDetected($result, $correlationId));
                
                Log::warning('Significant feature drift detected', [
                    'vertical' => $this->verticalCode,
                    'max_severity' => $result['summary']['max_severity'] ?? 'UNKNOWN',
                    'drift_detected_features' => $result['summary']['drift_detected_features'] ?? 0,
                    'correlation_id' => $correlationId,
                ]);
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('Feature drift detection failed', [
                'vertical' => $this->verticalCode,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'vertical_code' => $this->verticalCode,
                'overall_drift_detected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check drift for a single feature
     * 
     * @param string $featureName Feature name
     * @param array $expected Reference distribution
     * @param array $actual Current distribution
     * @return array Drift check result
     */
    protected function checkFeatureDrift(string $featureName, array $expected, array $actual): array
    {
        if (!$this->driftDetectionEnabled) {
            return [
                'drift_detected' => false,
                'drift_score' => 0.0,
                'reason' => 'disabled',
            ];
        }

        if ($this->driftDetector === null) {
            $this->driftDetector = app(FeatureDriftDetectorService::class);
        }

        try {
            $result = $this->driftDetector->detectDriftForFeature(
                $featureName,
                $this->verticalCode,
                $expected,
                $actual
            );

            if ($result['combined']['drift_detected'] ?? false) {
                event(new SignificantFeatureDriftDetected($result, Uuid::uuid4()->toString()));
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('Single feature drift detection failed', [
                'vertical' => $this->verticalCode,
                'feature' => $featureName,
                'error' => $e->getMessage(),
            ]);

            return [
                'drift_detected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Store reference distribution for a feature
     * 
     * @param string $featureName Feature name
     * @param array $distribution Reference distribution
     * @return bool Success status
     */
    protected function storeReferenceDistribution(string $featureName, array $distribution): bool
    {
        if ($this->driftDetector === null) {
            $this->driftDetector = app(FeatureDriftDetectorService::class);
        }

        return $this->driftDetector->storeReferenceDistribution(
            $featureName,
            $this->verticalCode,
            $distribution
        );
    }

    /**
     * Get reference distribution for a feature
     * 
     * @param string $featureName Feature name
     * @return array|null Reference distribution or null if not found
     */
    protected function getReferenceDistribution(string $featureName): ?array
    {
        if ($this->driftDetector === null) {
            $this->driftDetector = app(FeatureDriftDetectorService::class);
        }

        return $this->driftDetector->getReferenceDistribution($featureName, $this->verticalCode);
    }

    /**
     * Invalidate reference cache for this vertical
     * 
     * Вызывать при значительных изменениях в бизнес-логике вертикали
     */
    protected function invalidateReferenceCache(): void
    {
        if ($this->driftDetector === null) {
            $this->driftDetector = app(FeatureDriftDetectorService::class);
        }

        $this->driftDetector->invalidateReferenceCache($this->verticalCode);
        
        Log::info('Reference cache invalidated', [
            'vertical' => $this->verticalCode,
        ]);
    }

    /**
     * Get drift thresholds for the vertical
     * 
     * @return array Thresholds
     */
    protected function getDriftThresholds(): array
    {
        $config = Config::get("feature_drift.verticals.{$this->verticalCode}", []);
        $defaultThresholds = Config::get('feature_drift.default_thresholds', [
            'psi_critical' => 0.25,
            'psi_moderate' => 0.1,
            'ks_alpha' => 0.05,
            'js_critical' => 0.3,
            'js_moderate' => 0.1,
        ]);

        return array_merge($defaultThresholds, $config['thresholds'] ?? []);
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

    /**
     * Get monitored features for this vertical
     * 
     * @return array
     */
    public function getMonitoredFeatures(): array
    {
        return $this->monitoredFeatures;
    }
}
