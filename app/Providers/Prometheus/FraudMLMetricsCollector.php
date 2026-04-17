<?php declare(strict_types=1);

namespace App\Providers\Prometheus;

use Spatie\Prometheus\CollectorInterface;
use Spatie\Prometheus\Facades\Prometheus;
use Illuminate\Support\Facades\Cache;

/**
 * FraudMLMetricsCollector — Fraud ML metrics collector for Prometheus
 * 
 * Exports fraud detection ML metrics:
 * - Inference latency
 * - Fraud score distribution
 * - Blocked transactions count
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class FraudMLMetricsCollector implements CollectorInterface
{
    public function register(): void
    {
        Prometheus::addGauge()
            ->name('catvrf_fraud_ml_inference_latency_seconds')
            ->help('Fraud ML inference latency in seconds')
            ->label('model_version');

        Prometheus::addGauge()
            ->name('catvrf_fraud_score_avg')
            ->help('Average fraud score')
            ->label('vertical');

        Prometheus::addCounter()
            ->name('catvrf_fraud_blocked_by_ml_total')
            ->help('Fraud blocked by ML count')
            ->label('reason', 'vertical');

        Prometheus::addCounter()
            ->name('catvrf_fraud_inferences_total')
            ->help('Total fraud ML inferences')
            ->label('model_version', 'vertical');
    }

    public function collect(): void
    {
        // Get metrics from cache or Redis
        // In production, these would be aggregated from recent inference requests
        
        $verticals = ['medical', 'fraud', 'recommendation'];
        $modelVersion = $this->getActiveModelVersion();

        // Inference latency (sample from cache)
        $latency = Cache::get('fraud_ml:avg_latency', 0.05);
        
        Prometheus::addGauge()
            ->name('catvrf_fraud_ml_inference_latency_seconds')
            ->label('model_version', $this->sanitizeLabel($modelVersion))
            ->set($latency);

        // Average fraud score per vertical
        foreach ($verticals as $vertical) {
            $avgScore = Cache::get("fraud_ml:avg_score:{$vertical}", 0.1);
            $verticalLabel = $this->sanitizeLabel($vertical);

            Prometheus::addGauge()
                ->name('catvrf_fraud_score_avg')
                ->label('vertical', $verticalLabel)
                ->set($avgScore);

            // Blocked count
            $blockedCount = Cache::get("fraud_ml:blocked:{$vertical}", 0);
            
            Prometheus::addCounter()
                ->name('catvrf_fraud_blocked_by_ml_total')
                ->label('reason', 'high_score')
                ->label('vertical', $verticalLabel)
                ->set($blockedCount);

            // Total inferences
            $totalInferences = Cache::get("fraud_ml:inferences:{$vertical}", 0);
            
            Prometheus::addCounter()
                ->name('catvrf_fraud_inferences_total')
                ->label('model_version', $this->sanitizeLabel($modelVersion))
                ->label('vertical', $verticalLabel)
                ->set($totalInferences);
        }
    }

    private function getActiveModelVersion(): string
    {
        return Cache::get('fraud_model_active_version', 'unknown');
    }

    private function sanitizeLabel(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', substr($value, 0, 50));
    }
}
