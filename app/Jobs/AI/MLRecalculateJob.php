<?php declare(strict_types=1);

namespace App\Jobs\AI;


use App\Services\FraudMLService;
use App\Services\ML\FeatureDriftDetectorService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;

final class MLRecalculateJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    private string $correlationId;

    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('ml-training');
    }

    public function tags(): array
    {
        return ['ml', 'fraud', 'training', 'model-recalculation'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(12);
    }

    public function handle(FraudMLService $fraudMLService, FeatureDriftDetectorService $driftDetector): void
    {
        $driftDetectionEnabled = Config::get('fraud.drift_detection.enabled', true);

        try {
            $this->db->transaction(function () use ($fraudMLService, $driftDetector, $driftDetectionEnabled) {
                $trainingData = $fraudMLService->gatherTrainingData(
                    dateFrom: Carbon::now()->subDays(30),
                    dateTo: Carbon::now()
                );

                if ($trainingData->isEmpty()) {
                    $this->logger->channel('audit')->warning('Insufficient data for ML model training', [
                        'correlation_id' => $this->correlationId,
                        'date_range' => '30 days',
                    ]);

                    return;
                }

                $modelVersion = $fraudMLService->trainModel($trainingData);

                $metrics = $fraudMLService->evaluateModel($modelVersion);

                $this->db->table('fraud_model_versions')->insert([
                    'version' => $modelVersion,
                    'trained_at' => Carbon::now(),
                    'accuracy' => $metrics['accuracy'],
                    'precision' => $metrics['precision'],
                    'recall' => $metrics['recall'],
                    'f1_score' => $metrics['f1_score'],
                    'auc_roc' => $metrics['auc_roc'],
                    'file_path' => "storage/models/fraud/{$modelVersion}.joblib",
                    'comment' => "Auto-trained on " . Carbon::now()->toDateString(),
                ]);

                // Feature drift detection before model promotion
                if ($driftDetectionEnabled) {
                    $driftReport = $this->performDriftDetection($fraudMLService, $driftDetector, $modelVersion);

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

                        return;
                    }
                }

                // Switch to new model if performance improved AND no drift detected
                $currentVersion = $fraudMLService->getCurrentModelVersion();
                $currentMetrics = $fraudMLService->getModelMetrics($currentVersion);

                if ($metrics['auc_roc'] > ($currentMetrics['auc_roc'] + 0.02)) {
                    $fraudMLService->switchToModel($modelVersion);

                    $this->logger->channel('audit')->info('ML model switched to new version', [
                        'correlation_id' => $this->correlationId,
                        'old_version' => $currentVersion,
                        'new_version' => $modelVersion,
                        'old_auc' => $currentMetrics['auc_roc'],
                        'new_auc' => $metrics['auc_roc'],
                        'drift_checked' => $driftDetectionEnabled,
                    ]);
                } else {
                    $this->logger->channel('audit')->info('ML model training completed - performance not improved', [
                        'correlation_id' => $this->correlationId,
                        'new_version' => $modelVersion,
                        'auc_roc' => $metrics['auc_roc'],
                    ]);
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

            throw $e;
        }
    }

    /**
     * Perform feature drift detection for the new model
     * 
     * @param FraudMLService $fraudMLService
     * @param FeatureDriftDetectorService $driftDetector
     * @param string $newModelVersion
     * @return array Drift report
     */
    private function performDriftDetection(
        FraudMLService $fraudMLService,
        FeatureDriftDetectorService $driftDetector,
        string $newModelVersion
    ): array {
        $currentVersion = $fraudMLService->getCurrentModelVersion();
        $monitoredFeatures = Config::get('fraud.monitored_features', []);

        $featuresData = [];

        // Collect feature distributions for comparison
        foreach ($monitoredFeatures as $featureName) {
            $referenceDist = $driftDetector->getReferenceDistribution($currentVersion, $featureName);
            
            if ($referenceDist === null) {
                // No reference distribution exists - skip this feature
                continue;
            }

            // Get current distribution from recent data (last 7 days)
            $currentDist = $fraudMLService->getFeatureDistribution($featureName, Carbon::now()->subDays(7), Carbon::now());

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
            $newDist = $fraudMLService->getFeatureDistribution($featureName, Carbon::now()->subDays(30), Carbon::now());
            
            if (!empty($newDist)) {
                $driftDetector->storeReferenceDistribution($newModelVersion, $featureName, $newDist);
            }
        }

        // Run drift detection
        $driftReport = $driftDetector->detectDrift($featuresData);

        // Log drift detection results to ClickHouse
        $this->logDriftResultsToClickHouse($newModelVersion, $driftReport);

        return $driftReport;
    }

    /**
     * Enable shadow mode for a model version
     * 
     * @param string $modelVersion
     */
    private function enableShadowMode(string $modelVersion): void
    {
        $this->db->table('fraud_model_versions')
            ->where('version', $modelVersion)
            ->update([
                'is_shadow' => true,
                'shadow_created_at' => Carbon::now(),
                'shadow_predictions_count' => 0,
            ]);

        $this->logger->channel('audit')->info('Model enabled in shadow mode', [
            'model_version' => $modelVersion,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Log drift detection results to ClickHouse
     * 
     * @param string $modelVersion
     * @param array $driftReport
     */
    private function logDriftResultsToClickHouse(string $modelVersion, array $driftReport): void
    {
        try {
            $db = \ClickHouseDB::getInstance();
            $checkId = Str::uuid()->toString();

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
                'created_at' => Carbon::now(),
            ]]);

            // Log individual feature drift results
            $resultsToInsert = [];
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
                    'created_at' => Carbon::now(),
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
                    'created_at' => Carbon::now(),
                ];
            }

            if (!empty($resultsToInsert)) {
                $db->insert('feature_drift_detection_results', $resultsToInsert);
            }

            $this->logger->channel('audit')->info('Drift detection results logged to ClickHouse', [
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

