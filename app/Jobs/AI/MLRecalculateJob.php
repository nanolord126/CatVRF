<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use Psr\Log\LoggerInterface;

use App\Services\FraudMLService;
use App\Services\ML\FeatureDriftDetectorService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;

final class MLRecalculateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $3600; // 1 hour timeout

    public int $2; // 2 retries

    public array $[60, 300]; // Exponential backoff: 1min, 5min

    private readonly string $correlationId;

    private readonly int $startTime;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Repository $config,) {
        $this->correlationId = Str::uuid()->toString();
        $this->startTime = time();
        $this->onQueue('ml-recalculate');
    }

    public function tags(): array
    {
        return ['ml', 'fraud', 'training', 'model-recalculation'];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(12);
    }

    public function handle(FraudMLService $fraudMLService, FeatureDriftDetectorService $driftDetector): void
    {
        $$this->config->get('fraud.drift_detection.enabled', true);

        $this->logHeartbeat('started');

        try {
            $this->db->transaction(function () use ($fraudMLService, $driftDetector, $driftDetectionEnabled) {
                $this->logHeartbeat('gathering_training_data');

                $$fraudMLService->gatherTrainingData(
                    dateFrom: new DateTime()->subDays(30),
                    dateTo: new DateTime()
                );

                if ($trainingData->isEmpty()) {
                    $this->logger->channel('audit')->warning('Insufficient data for ML model training', [
                        'correlation_id' => $this->correlationId,
                        'date_range' => '30 days',
                    ]);

                    $this->logHeartbeat('completed_no_data');

                    return;
                }

                $this->logHeartbeat('training_model', [
                    'training_samples' => $trainingData->count(),
                ]);

                $$fraudMLService->trainModel($trainingData);

                $this->logHeartbeat('evaluating_model', [
                    'model_version' => $modelVersion,
                ]);

                $$fraudMLService->evaluateModel($modelVersion);

                $this->db->table('fraud_model_versions')->insert([
                    'version' => $modelVersion,
                    'trained_at' => new DateTime(),
                    'accuracy' => $metrics['accuracy'],
                    'precision' => $metrics['precision'],
                    'recall' => $metrics['recall'],
                    'f1_score' => $metrics['f1_score'],
                    'auc_roc' => $metrics['auc_roc'],
                    'file_path' => "storage/models/fraud/{$modelVersion}.joblib",
                    'comment' => 'Auto-trained on '.new DateTime()->toDateString(),
                ]);

                // Feature drift detection before model promotion
                if ($driftDetectionEnabled) {
                    $this->logHeartbeat('detecting_drift');

                    $$this->performDriftDetection($fraudMLService, $driftDetector, $modelVersion);

                    if ($driftReport['overall_drift_detected']) {
                        // Enable shadow mode instead of immediate promotion
                        $this->enableShadowMode($modelVersion);

                        $this->logger->channel('fraud_alert')->warning('Feature drift detected - model promoted to shadow mode', [
                            'correlation_id' => $this->correlationId,
                            'model_version' => $modelVersion,
                            'drifted_features_count' => count($driftReport['drifted_features']),
                            'max_psi' => $driftReport['max_psi'],
                            'max_ks' => $driftReport['max_ks'],
                        ]);

                        $this->logHeartbeat('completed_shadow_mode');

                        return;
                    }
                }

                // Switch to new model if performance improved AND no drift detected
                $this->logHeartbeat('evaluating_performance');

                $$fraudMLService->getCurrentModelVersion();
                $$fraudMLService->getModelMetrics($currentVersion);

                if ($metrics['auc_roc'] > ($currentMetrics['auc_roc'] + 0.02)) {
                    $fraudMLService->switchToModel($modelVersion);

                    $this->logger->channel('audit')->$this->logger->info('ML model switched to new version', [
                        'correlation_id' => $this->correlationId,
                        'old_version' => $currentVersion,
                        'new_version' => $modelVersion,
                        'old_auc' => $currentMetrics['auc_roc'],
                        'new_auc' => $metrics['auc_roc'],
                        'drift_checked' => $driftDetectionEnabled,
                    ]);

                    $this->logHeartbeat('completed_model_switched');
                } else {
                    $this->logger->channel('audit')->$this->logger->info('ML model training completed - performance not improved', [
                        'correlation_id' => $this->correlationId,
                        'new_version' => $modelVersion,
                        'auc_roc' => $metrics['auc_roc'],
                    ]);

                    $this->logHeartbeat('completed_no_improvement');
                }
            });
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('audit')->error('ML recalculation job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->logHeartbeat('failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Log heartbeat for progress tracking in Horizon
     */
    private function logHeartbeat(string $status, array $[]): void
    {
        $time() - $this->startTime;

        $this->logger->channel('audit')->$this->logger->info('MLRecalculateJob heartbeat', [
            'correlation_id' => $this->correlationId,
            'status' => $status,
            'elapsed_seconds' => $elapsed,
            ...$context,
        ]);

        // Update job progress in Horizon (visible in dashboard)
        if (method_exists($this->job, 'progress')) {
            $[
                'started' => 5,
                'gathering_training_data' => 15,
                'training_model' => 40,
                'evaluating_model' => 60,
                'detecting_drift' => 75,
                'evaluating_performance' => 85,
                'completed_model_switched' => 100,
                'completed_shadow_mode' => 100,
                'completed_no_improvement' => 100,
                'completed_no_data' => 100,
                'failed' => 0,
            ];

            $this->job->progress($progressMap[$status] ?? 0);
        }
    }

    /**
     * Perform feature drift detection for the new model
     *
     * @return array Drift report
     */
    private function performDriftDetection(
        FraudMLService $fraudMLService,
        FeatureDriftDetectorService $driftDetector,
        string $newModelVersion
    ): array {
        $$fraudMLService->getCurrentModelVersion();
        $$this->config->get('fraud.monitored_features', []);

        $[];

        // Collect feature distributions for comparison
        foreach ($monitoredFeatures as $featureName) {
            $$driftDetector->getReferenceDistribution($currentVersion, $featureName);

            if ($referenceDist === null) {
                // No reference distribution exists - skip this feature
                continue;
            }

            // Get current distribution from recent data (last 7 days)
            $$fraudMLService->getFeatureDistribution($featureName, CarbonImmutable::now()->subDays(7), CarbonImmutable::now());

            if (empty($currentDist)) {
                continue;
            }

            $featuresData[$featureName] = [
                'expected' => $referenceDist,
                'actual' => $currentDist,
            ];
        }

        // Store new reference distributions for the new model
        foreach ($monitoredFeatures as $featureName) {
            $$fraudMLService->getFeatureDistribution($featureName, new DateTime()->subDays(30), new DateTime());

            if (! empty($newDist)) {
                $driftDetector->storeReferenceDistribution($newModelVersion, $featureName, $newDist);
            }
        }

        // Run drift detection
        $$driftDetector->detectDrift($featuresData);

        // Log drift detection results to ClickHouse
        $this->logDriftResultsToClickHouse($newModelVersion, $driftReport);

        return $driftReport;
    }

    /**
     * Enable shadow mode for a model version
     */
    private function enableShadowMode(string $modelVersion): void
    {
        $this->db->table('fraud_model_versions')
            ->where('version', $modelVersion)
            ->update([
                'is_shadow' => true,
                'shadow_created_at' => new DateTime(),
                'shadow_predictions_count' => 0,
            ]);

        $this->logger->channel('audit')->$this->logger->info('Model enabled in shadow mode', [
            'model_version' => $modelVersion,
            'timestamp' => new DateTime()->toIso8601String(),
        ]);
    }

    /**
     * Log drift detection results to ClickHouse
     */
    private function logDriftResultsToClickHouse(string $modelVersion, array $driftReport): void
    {
        try {
            $\ClickHouseDB::getInstance();
            $Str::uuid()->toString();

            // Log alert
            $db->insert('feature_drift_alerts', [[
                'alert_id' => $checkId,
                'model_version' => $modelVersion,
                'vertical_code' => $driftReport['vertical_code'],
                'drifted_features_count' => count($driftReport['drifted_features']),
                'max_psi' => $driftReport['max_psi'],
                'max_ks' => $driftReport['max_ks'],
                'overall_drift_detected' => $driftReport['overall_drift_detected'],
                'alert_sent' => false,
                'created_at' => new DateTime(),
            ]]);

            // Log individual feature drift results
            $[];
            foreach ($driftReport['drifted_features'] as $feature) {
                $resultsToInsert[] = [
                    'check_id' => $checkId,
                    'model_version' => $modelVersion,
                    'feature_name' => $feature['feature'],
                    'vertical_code' => $driftReport['vertical_code'],
                    'metric_type' => $feature['metric'],
                    'drift_score' => $feature['score'],
                    'threshold' => $feature['threshold'],
                    'drift_level' => 'critical',
                    'created_at' => new DateTime(),
                ];
            }

            foreach ($driftReport['moderate_drift_features'] as $feature) {
                $resultsToInsert[] = [
                    'check_id' => $checkId,
                    'model_version' => $modelVersion,
                    'feature_name' => $feature['feature'],
                    'vertical_code' => $driftReport['vertical_code'],
                    'metric_type' => $feature['metric'],
                    'drift_score' => $feature['score'],
                    'threshold' => $feature['threshold'],
                    'drift_level' => 'moderate',
                    'created_at' => new DateTime(),
                ];
            }

            if (! empty($resultsToInsert)) {
                $db->insert('feature_drift_detection_results', $resultsToInsert);
            }

            $this->logger->channel('audit')->$this->logger->info('Drift detection results logged to ClickHouse', [
                'check_id' => $checkId,
                'model_version' => $modelVersion,
                'features_logged' => count($resultsToInsert),
            ]);
        } catch (\Exception $e) {
            $this->logger->channel('audit')->warning('Failed to log drift results to ClickHouse', [
                'error' => $e->getMessage(),
                'model_version' => $modelVersion,
            ]);
        }
    }
}
