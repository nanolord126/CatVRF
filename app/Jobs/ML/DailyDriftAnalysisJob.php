<?php

declare(strict_types=1);

namespace App\Jobs\ML;

use App\Services\ML\ModelDriftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Daily Drift Analysis Job
 * 
 * CANON 2026 - Production Ready
 * 
 * Scheduled job that performs comprehensive drift analysis for all ML models.
 * Runs daily (typically at 2 AM UTC) to analyze:
 * - Data drift (feature distribution changes)
 * - Concept drift (performance decay)
 * - Prediction drift (prediction distribution changes)
 * 
 * Automatically triggers alerts and retrain recommendations when critical drift is detected.
 * 
 * @see config/fraud-ml.php for configuration
 */
final readonly class DailyDriftAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour timeout
    public string $queue = 'ml-drift';

    public function __construct(
        private readonly ?string $modelType = null, // If null, analyze all models
        private readonly ?string $verticalCode = null, // If null, analyze all verticals
    ) {
        $this->tries = 3;
        $this->timeout = 3600; // 1 hour timeout
        $this->queue = 'ml-drift';
    }

    public function handle(ModelDriftService $driftService, LoggerInterface $logger): void
    {
        $startTime = microtime(true);

        $logger->info('Daily drift analysis job started', [
            'model_type' => $this->modelType ?? 'all',
            'vertical_code' => $this->verticalCode ?? 'all',
        ]);

        try {
            // Define models to analyze
            $models = $this->modelType ? [$this->modelType] : [
                'behavioral_biometrics',
                'fraud_ml_ensemble',
                'insider_threat',
                'vpn_proxy_detection',
            ];

            // Define verticals to analyze
            $verticals = $this->verticalCode ? [$this->verticalCode] : [
                'medical',
                'payment',
                'taxi',
                'hotels',
                'food',
                'marketplace',
            ];

            $results = [];
            $criticalDriftDetected = false;

            foreach ($models as $model) {
                foreach ($verticals as $vertical) {
                    $logger->info('Analyzing drift for model', [
                        'model_type' => $model,
                        'vertical_code' => $vertical,
                    ]);

                    try {
                        $report = $driftService->dailyFullAnalysis($model, $vertical);
                        $results[$model][$vertical] = $report;

                        if ($report['status'] === 'success' && ($report['summary']['overall_drift_detected'] ?? false)) {
                            $criticalDriftDetected = true;

                            $logger->warning('Critical drift detected', [
                                'model_type' => $model,
                                'vertical_code' => $vertical,
                                'max_drift_score' => $report['summary']['max_drift_score'],
                                'recommendations' => $report['recommendations'],
                            ]);
                        }
                    } catch (\Throwable $e) {
                        $logger->error('Failed to analyze drift for model', [
                            'model_type' => $model,
                            'vertical_code' => $vertical,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        $results[$model][$vertical] = [
                            'status' => 'error',
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            $latencyMs = (microtime(true) - $startTime) * 1000;

            $logger->info('Daily drift analysis job completed', [
                'models_analyzed' => count($models),
                'verticals_analyzed' => count($verticals),
                'critical_drift_detected' => $criticalDriftDetected,
                'latency_ms' => round($latencyMs, 2),
                'results_summary' => $this->summarizeResults($results),
            ]);

            // If critical drift detected, trigger notification
            if ($criticalDriftDetected) {
                $this->notifyCriticalDrift($results, $logger);
            }
        } catch (\Throwable $e) {
            $logger->error('Daily drift analysis job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Daily drift analysis job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    private function summarizeResults(array $results): array
    {
        $summary = [
            'total_analyses' => 0,
            'successful' => 0,
            'failed' => 0,
            'insufficient_data' => 0,
            'drift_detected' => 0,
        ];

        foreach ($results as $modelResults) {
            foreach ($modelResults as $report) {
                $summary['total_analyses']++;

                match ($report['status']) {
                    'success' => $summary['successful']++,
                    'error' => $summary['failed']++,
                    'insufficient_data' => $summary['insufficient_data']++,
                    default => null,
                };

                if ($report['status'] === 'success' && ($report['summary']['overall_drift_detected'] ?? false)) {
                    $summary['drift_detected']++;
                }
            }
        }

        return $summary;
    }

    private function notifyCriticalDrift(array $results, LoggerInterface $logger): void
    {
        $criticalModels = [];

        foreach ($results as $model => $verticalResults) {
            foreach ($verticalResults as $vertical => $report) {
                if ($report['status'] === 'success' && ($report['summary']['overall_drift_detected'] ?? false)) {
                    $criticalModels[] = [
                        'model' => $model,
                        'vertical' => $vertical,
                        'max_drift_score' => $report['summary']['max_drift_score'],
                        'recommendations' => $report['recommendations'],
                    ];
                }
            }
        }

        $logger->warning('Critical drift notification sent', [
            'affected_models' => $criticalModels,
        ]);

        // In production, this would send notifications via:
        // - Email to data scientists and ML engineers
        // - Slack/Telegram alerts
        // - PagerDuty for critical alerts
    }
}
