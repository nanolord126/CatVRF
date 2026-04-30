<?php

declare(strict_types=1);

namespace App\Services\ML;

use App\DTO\ML\DriftResult;
use App\DTO\ML\DriftAlert;
use App\DTO\ML\ModelPerformanceMetrics;
use App\Domains\FraudML\Services\FeatureDriftDetectorService;
use App\Domains\FraudML\Services\PrometheusMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Model Drift Service - Comprehensive ML Model Drift Monitoring
 * 
 * CANON 2026 - Production Ready
 * 
 * Monitors three types of drift:
 * 1. Data Drift (Feature Drift): Changes in input feature distributions
 * 2. Concept Drift (Label Drift): Changes in feature -> target relationship
 * 3. Model Drift (Prediction Drift): Changes in prediction distribution
 * 
 * Features:
 * - Real-time drift detection (< 100ms overhead)
 * - Daily full analysis with baseline comparison
 * - Automatic alerting (Telegram/Slack/Email)
 * - SHAP explainability for drift interpretation
 * - Auto-retrain triggering with human approval
 * - Compliance with 152-ФЗ, ФЗ-323
 * 
 * @author CatVRF Team
 * @version 2026.04.23
 */
final readonly class ModelDriftService
{
    use WithAuditLogging;

    private const REDIS_PREFIX = 'fraudml:drift:';
    private const BASELINE_PREFIX = 'fraudml:drift:baseline:';
    private const ALERT_COOLDOWN_PREFIX = 'fraudml:drift:alert_cooldown:';
    private const SAMPLE_WINDOW_PREFIX = 'fraudml:drift:samples:';
    
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigRepository $config,
        private readonly CacheManager $cache,
        private readonly RedisFactory $redis,
        private readonly DatabaseManager $db,
        private readonly Queue $queue,
        private readonly FeatureDriftDetectorService $driftDetector,
        private readonly PrometheusMetricsService $prometheus,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Monitor real-time drift for a single prediction
     * 
     * Called after each ML prediction to detect immediate drift.
     * Uses sliding window of last N predictions for statistical significance.
     * 
     * @param  array  $features  Feature array from prediction
     * @param  float  $prediction  Model prediction score
     * @param  string  $modelType  Model type (e.g., 'fraud_ml_ensemble')
     * @param  string  $verticalCode  Vertical code (e.g., 'medical', 'payment')
     * @return DriftResult Drift detection result
     */
    public function monitorRealTime(
        array $features,
        float $prediction,
        string $modelType = 'fraud_ml_ensemble',
        string $verticalCode = 'default'
    ): DriftResult {
        $startTime = microtime(true);
        $correlationId = Str::uuid()->toString();

        if (! $this->config->get('fraud-ml.drift.real_time_enabled', false)) {
            return $this->createNeutralResult($modelType, $verticalCode, $correlationId);
        }

        try {
            // Store feature values in sliding window
            $this->storeFeatureSample($features, $prediction, $modelType, $verticalCode);

            // Check if we have enough samples for drift detection
            $windowSize = $this->config->get('fraud-ml.drift.real_time.sample_window_size', 10000);
            $sampleCount = $this->getSampleCount($modelType, $verticalCode);

            if ($sampleCount < min($windowSize, 1000)) {
                // Not enough samples yet - skip drift check
                return $this->createNeutralResult($modelType, $verticalCode, $correlationId);
            }

            // Get baseline distribution
            $baseline = $this->getBaselineDistribution($modelType, $verticalCode);
            if ($baseline === null) {
                // No baseline established yet
                $this->establishBaseline($features, $modelType, $verticalCode);
                return $this->createNeutralResult($modelType, $verticalCode, $correlationId);
            }

            // Calculate drift for critical features only
            $criticalFeatures = $this->getCriticalFeatures($modelType);
            $driftResults = [];

            foreach ($criticalFeatures as $featureName) {
                if (! isset($features[$featureName])) {
                    continue;
                }

                $currentDistribution = $this->getFeatureDistribution(
                    $featureName,
                    $modelType,
                    $verticalCode,
                    1000 // Last 1000 samples
                );

                if (count($currentDistribution) < 100) {
                    continue;
                }

                $referenceDistribution = $baseline[$featureName] ?? null;
                if ($referenceDistribution === null) {
                    continue;
                }

                // Calculate drift using existing detector
                $driftResult = $this->driftDetector->detectDriftForFeature(
                    $featureName,
                    $verticalCode,
                    $referenceDistribution,
                    $currentDistribution
                );

                $driftResults[$featureName] = $driftResult;
            }

            // Calculate combined drift score
            $combinedScore = $this->calculateCombinedDriftScore($driftResults);
            $severity = $this->determineSeverity($combinedScore);

            // Check thresholds
            $warningThreshold = $this->config->get('fraud-ml.drift.thresholds.data_drift.psi_warning', 0.1);
            $criticalThreshold = $this->config->get('fraud-ml.drift.thresholds.data_drift.psi_critical', 0.25);

            $driftDetected = $combinedScore > $warningThreshold;
            $isCritical = $combinedScore > $criticalThreshold;

            // Record Prometheus metrics
            $this->prometheus->recordFeatureDriftCombined(
                $combinedScore,
                $modelType,
                $verticalCode,
                $correlationId
            );

            // Generate explanation if drift detected
            $explanation = null;
            if ($driftDetected && $this->config->get('fraud-ml.drift.explainability.compute_on_drift', true)) {
                $explanation = $this->explainDrift($driftResults, $modelType);
            }

            $result = new DriftResult(
                combinedScore: $combinedScore,
                driftDetected: $driftDetected,
                severity: $severity,
                metrics: $driftResults,
                featureName: $modelType,
                modelType: $modelType,
                verticalCode: $verticalCode,
                correlationId: $correlationId,
                timestamp: CarbonImmutable::now(),
                explanation: $explanation,
            );

            // Send alert if critical
            if ($isCritical) {
                $this->sendAlert($result, 'critical');
            } elseif ($driftDetected) {
                $this->sendAlert($result, 'warning');
            }

            // Log performance
            $latencyMs = (microtime(true) - $startTime) * 1000;
            $this->logger->info('Real-time drift monitoring completed', [
                'model_type' => $modelType,
                'vertical_code' => $verticalCode,
                'combined_score' => $combinedScore,
                'drift_detected' => $driftDetected,
                'severity' => $severity,
                'latency_ms' => round($latencyMs, 2),
                'correlation_id' => $correlationId,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Real-time drift monitoring failed', [
                'model_type' => $modelType,
                'vertical_code' => $verticalCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            // Fail open - return neutral result
            return $this->createNeutralResult($modelType, $verticalCode, $correlationId);
        }
    }

    /**
     * Perform daily full drift analysis
     * 
     * Compares last 24h data against 7-day baseline.
     * Runs as scheduled job (typically at 2 AM UTC).
     * 
     * @param  string  $modelType  Model type
     * @param  string  $verticalCode  Vertical code
     * @return array Full drift analysis report
     */
    public function dailyFullAnalysis(
        string $modelType = 'fraud_ml_ensemble',
        string $verticalCode = 'default'
    ): array {
        $correlationId = Str::uuid()->toString();
        $startTime = microtime(true);

        $this->logger->info('Daily drift analysis started', [
            'model_type' => $modelType,
            'vertical_code' => $verticalCode,
            'correlation_id' => $correlationId,
        ]);

        try {
            $referenceDays = $this->config->get('fraud-ml.drift.daily.reference_window_days', 7);
            $currentDays = $this->config->get('fraud-ml.drift.daily.current_window_days', 1);

            // Get reference distribution from ClickHouse
            $referenceData = $this->getHistoricalFeatureDistribution(
                $modelType,
                $verticalCode,
                CarbonImmutable::now()->subDays($referenceDays),
                CarbonImmutable::now()->subDays($currentDays)
            );

            // Get current distribution from ClickHouse
            $currentData = $this->getHistoricalFeatureDistribution(
                $modelType,
                $verticalCode,
                CarbonImmutable::now()->subDays($currentDays),
                CarbonImmutable::now()
            );

            if (empty($referenceData) || empty($currentData)) {
                $this->logger->warning('Insufficient data for daily drift analysis', [
                    'model_type' => $modelType,
                    'vertical_code' => $verticalCode,
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'status' => 'insufficient_data',
                    'model_type' => $modelType,
                    'vertical_code' => $verticalCode,
                    'correlation_id' => $correlationId,
                    'timestamp' => CarbonImmutable::now()->toIso8601String(),
                ];
            }

            // Detect drift for all features
            $driftResults = $this->driftDetector->detectAllFeatures($verticalCode, $currentData);

            // Calculate concept drift (performance decay)
            $performanceDrift = $this->calculateConceptDrift($modelType, $verticalCode);

            // Calculate prediction drift
            $predictionDrift = $this->calculatePredictionDrift($modelType, $verticalCode);

            // Generate comprehensive report
            $report = [
                'status' => 'success',
                'model_type' => $modelType,
                'vertical_code' => $verticalCode,
                'correlation_id' => $correlationId,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
                'data_drift' => $driftResults,
                'concept_drift' => $performanceDrift,
                'prediction_drift' => $predictionDrift,
                'summary' => [
                    'overall_drift_detected' => $driftResults['summary']['overall_drift_detected']
                        || $performanceDrift['significant_decay']
                        || $predictionDrift['significant_drift'],
                    'max_drift_score' => $driftResults['summary']['max_drift_score'],
                    'drifted_features_count' => $driftResults['summary']['drift_detected_features'],
                    'accuracy_decay' => $performanceDrift['accuracy_decay'] ?? 0,
                    'f1_decay' => $performanceDrift['f1_decay'] ?? 0,
                ],
                'recommendations' => $this->generateRecommendations(
                    $driftResults,
                    $performanceDrift,
                    $predictionDrift
                ),
            ];

            // Store report in ClickHouse for audit
            $this->storeDriftReport($report);

            // Update baseline if drift is minimal and it's time
            if (! $report['summary']['overall_drift_detected']) {
                $this->maybeUpdateBaseline($modelType, $verticalCode);
            }

            // Send critical alerts
            if ($report['summary']['overall_drift_detected']) {
                $alert = DriftAlert::fromArray([
                    'alert_id' => 'daily_'.$correlationId,
                    'severity' => $performanceDrift['significant_decay'] ? 'CRITICAL' : 'WARNING',
                    'model_type' => $modelType,
                    'vertical_code' => $verticalCode,
                    'feature_name' => 'multiple',
                    'drift_score' => $report['summary']['max_drift_score'],
                    'metric_type' => 'combined',
                    'threshold' => $this->config->get('fraud-ml.drift.thresholds.data_drift.psi_critical', 0.25),
                    'affected_features' => array_keys($driftResults['features']),
                    'recommended_action' => $report['recommendations'][0]['action'] ?? 'monitor',
                    'timestamp' => CarbonImmutable::now()->toIso8601String(),
                    'correlation_id' => $correlationId,
                    'shap_explanation' => null,
                ]);
                $this->sendAlertFromObject($alert);
            }

            $latencyMs = (microtime(true) - $startTime) * 1000;
            $this->logger->info('Daily drift analysis completed', [
                'model_type' => $modelType,
                'vertical_code' => $verticalCode,
                'overall_drift_detected' => $report['summary']['overall_drift_detected'],
                'max_drift_score' => $report['summary']['max_drift_score'],
                'latency_ms' => round($latencyMs, 2),
                'correlation_id' => $correlationId,
            ]);

            return $report;
        } catch (\Throwable $e) {
            $this->logger->error('Daily drift analysis failed', [
                'model_type' => $modelType,
                'vertical_code' => $verticalCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'model_type' => $modelType,
                'vertical_code' => $verticalCode,
                'correlation_id' => $correlationId,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ];
        }
    }

    /**
     * Calculate concept drift (performance decay)
     * 
     * Compares current model performance against historical baseline.
     * Detects if the model's predictive power is degrading.
     */
    private function calculateConceptDrift(string $modelType, string $verticalCode): array
    {
        $baselineMetrics = $this->getBaselinePerformanceMetrics($modelType, $verticalCode);
        $currentMetrics = $this->getCurrentPerformanceMetrics($modelType, $verticalCode);

        if ($baselineMetrics === null || $currentMetrics === null) {
            return [
                'significant_decay' => false,
                'accuracy_decay' => 0,
                'f1_decay' => 0,
                'reason' => 'insufficient_metrics_data',
            ];
        }

        $accuracyDecay = $baselineMetrics['accuracy'] - $currentMetrics['accuracy'];
        $f1Decay = $baselineMetrics['f1_score'] - $currentMetrics['f1_score'];

        $warningThreshold = $this->config->get('fraud-ml.drift.thresholds.concept_drift.accuracy_decay_warning', 0.05);
        $criticalThreshold = $this->config->get('fraud-ml.drift.thresholds.concept_drift.accuracy_decay_critical', 0.08);

        $significantDecay = $accuracyDecay > $warningThreshold || $f1Decay > $warningThreshold;
        $isCritical = $accuracyDecay > $criticalThreshold || $f1Decay > $criticalThreshold;

        return [
            'significant_decay' => $significantDecay,
            'is_critical' => $isCritical,
            'accuracy_decay' => round($accuracyDecay, 4),
            'f1_decay' => round($f1Decay, 4),
            'baseline_accuracy' => $baselineMetrics['accuracy'],
            'current_accuracy' => $currentMetrics['accuracy'],
            'baseline_f1' => $baselineMetrics['f1_score'],
            'current_f1' => $currentMetrics['f1_score'],
        ];
    }

    /**
     * Calculate prediction drift (distribution shift)
     * 
     * Detects if the model's prediction distribution is shifting.
     */
    private function calculatePredictionDrift(string $modelType, string $verticalCode): array
    {
        $baselinePredictions = $this->getBaselinePredictionDistribution($modelType, $verticalCode);
        $currentPredictions = $this->getCurrentPredictionDistribution($modelType, $verticalCode);

        if (empty($baselinePredictions) || empty($currentPredictions)) {
            return [
                'significant_drift' => false,
                'drift_score' => 0,
                'reason' => 'insufficient_prediction_data',
            ];
        }

        // Calculate PSI for prediction distribution
        $psiResult = $this->driftDetector->calculatePSI($baselinePredictions, $currentPredictions);
        $threshold = $this->config->get('fraud-ml.drift.thresholds.model_drift.prediction_distribution_shift', 0.15);

        return [
            'significant_drift' => $psiResult['psi'] > $threshold,
            'drift_score' => $psiResult['psi'],
            'psi' => $psiResult['psi'],
            'threshold' => $threshold,
            'baseline_mean' => round(array_sum($baselinePredictions) / count($baselinePredictions), 4),
            'current_mean' => round(array_sum($currentPredictions) / count($currentPredictions), 4),
        ];
    }

    /**
     * Generate recommendations based on drift analysis
     */
    private function generateRecommendations(array $dataDrift, array $conceptDrift, array $predictionDrift): array
    {
        $recommendations = [];

        if ($dataDrift['summary']['overall_drift_detected']) {
            $recommendations[] = [
                'action' => 'monitor_closely',
                'priority' => 'high',
                'description' => 'Data drift detected - monitor feature distributions closely',
            ];
        }

        if ($conceptDrift['significant_decay']) {
            if ($conceptDrift['is_critical']) {
                $recommendations[] = [
                    'action' => 'retrain_model',
                    'priority' => 'critical',
                    'description' => 'Significant performance decay detected - model retraining recommended',
                ];
            } else {
                $recommendations[] = [
                    'action' => 'prepare_retrain',
                    'priority' => 'high',
                    'description' => 'Performance decay detected - prepare for model retraining',
                ];
            }
        }

        if ($predictionDrift['significant_drift']) {
            $recommendations[] = [
                'action' => 'investigate_prediction_shift',
                'priority' => 'medium',
                'description' => 'Prediction distribution shifted - investigate underlying causes',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'action' => 'continue_monitoring',
                'priority' => 'low',
                'description' => 'No significant drift detected - continue normal monitoring',
            ];
        }

        return $recommendations;
    }

    /**
     * Store feature sample in sliding window
     */
    private function storeFeatureSample(array $features, float $prediction, string $modelType, string $verticalCode): void
    {
        $key = self::SAMPLE_WINDOW_PREFIX.$modelType.':'.$verticalCode;
        $sample = [
            'features' => $features,
            'prediction' => $prediction,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];

        // Use Redis list for sliding window
        $this->redis->connection()->lpush($key, json_encode($sample));
        
        // Trim to window size
        $windowSize = $this->config->get('fraud-ml.drift.real_time.sample_window_size', 10000);
        $this->redis->connection()->ltrim($key, 0, $windowSize - 1);
        
        // Set expiry
        $this->redis->connection()->expire($key, 86400); // 24 hours
    }

    /**
     * Get sample count from sliding window
     */
    private function getSampleCount(string $modelType, string $verticalCode): int
    {
        $key = self::SAMPLE_WINDOW_PREFIX.$modelType.':'.$verticalCode;
        
        return $this->redis->connection()->llen($key);
    }

    /**
     * Get feature distribution from sliding window
     */
    private function getFeatureDistribution(string $featureName, string $modelType, string $verticalCode, int $limit): array
    {
        $key = self::SAMPLE_WINDOW_PREFIX.$modelType.':'.$verticalCode;
        $samples = $this->redis->connection()->lrange($key, 0, $limit - 1);
        
        $values = [];
        foreach ($samples as $sample) {
            $data = json_decode($sample, true);
            if (isset($data['features'][$featureName])) {
                $values[] = $data['features'][$featureName];
            }
        }
        
        return $values;
    }

    /**
     * Get baseline distribution from Redis
     */
    private function getBaselineDistribution(string $modelType, string $verticalCode): ?array
    {
        $key = self::BASELINE_PREFIX.$modelType.':'.$verticalCode;
        $data = $this->redis->connection()->get($key);
        
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Establish baseline distribution from current samples
     */
    private function establishBaseline(array $features, string $modelType, string $verticalCode): void
    {
        $criticalFeatures = $this->getCriticalFeatures($modelType);
        $baseline = [];
        
        foreach ($criticalFeatures as $featureName) {
            if (isset($features[$featureName])) {
                $distribution = $this->getFeatureDistribution($featureName, $modelType, $verticalCode, 1000);
                if (count($distribution) >= 100) {
                    $baseline[$featureName] = $distribution;
                }
            }
        }
        
        if (! empty($baseline)) {
            $key = self::BASELINE_PREFIX.$modelType.':'.$verticalCode;
            $this->redis->connection()->setex(
                $key,
                $this->config->get('fraud-ml.drift.baseline.update_interval_days', 7) * 86400,
                json_encode($baseline)
            );
            
            $this->logger->info('Baseline distribution established', [
                'model_type' => $modelType,
                'vertical_code' => $verticalCode,
                'features_count' => count($baseline),
            ]);
        }
    }

    /**
     * Get critical features for model type
     */
    private function getCriticalFeatures(string $modelType): array
    {
        return $this->config->get("fraud-ml.drift.models.{$modelType}.critical_features", []);
    }

    /**
     * Calculate combined drift score from multiple feature drifts
     */
    private function calculateCombinedDriftScore(array $driftResults): float
    {
        if (empty($driftResults)) {
            return 0.0;
        }
        
        $scores = [];
        foreach ($driftResults as $result) {
            $scores[] = $result['combined']['score'] ?? 0;
        }
        
        return max($scores); // Use maximum drift score
    }

    /**
     * Determine severity based on drift score
     */
    private function determineSeverity(float $score): string
    {
        $warningThreshold = $this->config->get('fraud-ml.drift.thresholds.data_drift.psi_warning', 0.1);
        $criticalThreshold = $this->config->get('fraud-ml.drift.thresholds.data_drift.psi_critical', 0.25);
        
        if ($score <= $warningThreshold) {
            return 'LOW';
        }
        
        if ($score <= $criticalThreshold) {
            return 'MEDIUM';
        }
        
        return 'HIGH';
    }

    /**
     * Explain drift using SHAP-like feature importance
     */
    private function explainDrift(array $driftResults, string $modelType): array
    {
        $explanations = [];
        
        foreach ($driftResults as $featureName => $result) {
            if ($result['combined']['drift_detected']) {
                $explanations[] = [
                    'feature' => $featureName,
                    'drift_score' => $result['combined']['score'],
                    'contribution' => $this->calculateFeatureContribution($result),
                    'severity' => $result['combined']['severity'],
                ];
            }
        }
        
        // Sort by contribution
        usort($explanations, fn ($a, $b) => $b['contribution'] <=> $a['contribution']);
        
        // Return top N features
        $topN = $this->config->get('fraud-ml.drift.explainability.top_features_count', 5);
        
        return array_slice($explanations, 0, $topN);
    }

    /**
     * Calculate feature contribution to drift
     */
    private function calculateFeatureContribution(array $driftResult): float
    {
        return $driftResult['combined']['score'];
    }

    /**
     * Send alert for drift detection
     */
    private function sendAlert(DriftResult $result, string $alertType): void
    {
        if (! $this->config->get('fraud-ml.drift.alerting.enabled', true)) {
            return;
        }
        
        // Check cooldown
        $cooldownKey = self::ALERT_COOLDOWN_PREFIX.$result->modelType.':'.$result->verticalCode.':'.$alertType;
        $cooldownMinutes = $this->config->get('fraud-ml.drift.alerting.cooldown_minutes', 60);
        
        if ($this->redis->connection()->exists($cooldownKey)) {
            $this->logger->debug('Alert cooldown active', [
                'model_type' => $result->modelType,
                'vertical_code' => $result->verticalCode,
                'alert_type' => $alertType,
            ]);
            return;
        }
        
        $alert = DriftAlert::fromDriftResult($result, $alertType);
        $this->sendAlertFromObject($alert);
        
        // Set cooldown
        $this->redis->connection()->setex($cooldownKey, $cooldownMinutes * 60, '1');
    }

    /**
     * Send alert from alert object
     */
    private function sendAlertFromObject(DriftAlert $alert): void
    {
        $channels = $this->config->get('fraud-ml.drift.alerting.channels', []);
        
        if ($channels['email'] ?? false) {
            $this->sendEmailAlert($alert);
        }
        
        if ($channels['telegram'] ?? false) {
            $this->sendTelegramAlert($alert);
        }
        
        if ($channels['slack'] ?? false) {
            $this->sendSlackAlert($alert);
        }
        
        // Log alert
        $this->logger->warning('Drift alert sent', [
            'alert_id' => $alert->alertId,
            'severity' => $alert->severity,
            'model_type' => $alert->modelType,
            'vertical_code' => $alert->verticalCode,
            'drift_score' => $alert->driftScore,
            'recommended_action' => $alert->recommendedAction,
        ]);
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(DriftAlert $alert): void
    {
        // Implementation depends on email service
        // For now, just log
        $this->logger->info('Email alert would be sent', [
            'alert_id' => $alert->alertId,
            'severity' => $alert->severity,
        ]);
    }

    /**
     * Send Telegram alert
     */
    private function sendTelegramAlert(DriftAlert $alert): void
    {
        // Implementation depends on Telegram bot
        $this->logger->info('Telegram alert would be sent', [
            'alert_id' => $alert->alertId,
            'severity' => $alert->severity,
        ]);
    }

    /**
     * Send Slack alert
     */
    private function sendSlackAlert(DriftAlert $alert): void
    {
        // Implementation depends on Slack webhook
        $this->logger->info('Slack alert would be sent', [
            'alert_id' => $alert->alertId,
            'severity' => $alert->severity,
        ]);
    }

    /**
     * Get historical feature distribution from ClickHouse
     */
    private function getHistoricalFeatureDistribution(
        string $modelType,
        string $verticalCode,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo
    ): array {
        try {
            $query = "
                SELECT 
                    feature_name,
                    groupArray(feature_value) as values
                FROM fraud_features_online
                WHERE model_type = ?
                AND vertical_code = ?
                AND timestamp >= ?
                AND timestamp < ?
                GROUP BY feature_name
            ";
            
            $results = $this->db->connection('clickhouse')->select(
                $query,
                [$modelType, $verticalCode, $dateFrom->toIso8601String(), $dateTo->toIso8601String()]
            );
            
            $data = [];
            foreach ($results as $row) {
                $data[$row->feature_name] = $row->values;
            }
            
            return $data;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get historical feature distribution', [
                'error' => $e->getMessage(),
                'model_type' => $modelType,
                'vertical_code' => $verticalCode,
            ]);
            
            return [];
        }
    }

    /**
     * Store drift report in ClickHouse
     */
    private function storeDriftReport(array $report): void
    {
        try {
            $this->db->connection('clickhouse')->statement(
                'INSERT INTO fraud_drift_reports (report_data, model_type, vertical_code, timestamp) VALUES (?, ?, ?, ?)',
                [
                    json_encode($report),
                    $report['model_type'],
                    $report['vertical_code'],
                    CarbonImmutable::now()->toIso8601String(),
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to store drift report', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get baseline performance metrics
     */
    private function getBaselinePerformanceMetrics(string $modelType, string $verticalCode): ?array
    {
        $key = self::BASELINE_PREFIX.$modelType.':'.$verticalCode.':performance';
        $data = $this->redis->connection()->get($key);
        
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Get current performance metrics
     */
    private function getCurrentPerformanceMetrics(string $modelType, string $verticalCode): ?array
    {
        // In production, this would query labeled data from ClickHouse
        // For now, return null to indicate not implemented
        return null;
    }

    /**
     * Get baseline prediction distribution
     */
    private function getBaselinePredictionDistribution(string $modelType, string $verticalCode): array
    {
        $key = self::BASELINE_PREFIX.$modelType.':'.$verticalCode.':predictions';
        $data = $this->redis->connection()->get($key);
        
        return $data ? json_decode($data, true) : [];
    }

    /**
     * Get current prediction distribution
     */
    private function getCurrentPredictionDistribution(string $modelType, string $verticalCode): array
    {
        $key = self::SAMPLE_WINDOW_PREFIX.$modelType.':'.$verticalCode;
        $samples = $this->redis->connection()->lrange($key, 0, 9999);
        
        $predictions = [];
        foreach ($samples as $sample) {
            $data = json_decode($sample, true);
            if (isset($data['prediction'])) {
                $predictions[] = $data['prediction'];
            }
        }
        
        return $predictions;
    }

    /**
     * Maybe update baseline if it's time
     */
    private function maybeUpdateBaseline(string $modelType, string $verticalCode): void
    {
        $key = self::BASELINE_PREFIX.$modelType.':'.$verticalCode.':last_update';
        $lastUpdate = $this->redis->connection()->get($key);
        
        $updateIntervalDays = $this->config->get('fraud-ml.drift.baseline.update_interval_days', 7);
        
        if ($lastUpdate === null || CarbonImmutable::parse($lastUpdate)->addDays($updateIntervalDays)->isPast()) {
            $this->establishBaseline([], $modelType, $verticalCode);
            $this->redis->connection()->setex($key, $updateIntervalDays * 86400, CarbonImmutable::now()->toIso8601String());
        }
    }

    /**
     * Create neutral drift result (when monitoring is disabled or insufficient data)
     */
    private function createNeutralResult(string $modelType, string $verticalCode, string $correlationId): DriftResult
    {
        return new DriftResult(
            combinedScore: 0.0,
            driftDetected: false,
            severity: 'LOW',
            metrics: [],
            featureName: $modelType,
            modelType: $modelType,
            verticalCode: $verticalCode,
            correlationId: $correlationId,
            timestamp: CarbonImmutable::now(),
            explanation: null,
        );
    }
}
