<?php declare(strict_types=1);

namespace App\Providers\Prometheus;

use Spatie\Prometheus\CollectorInterface;
use Spatie\Prometheus\Facades\Prometheus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * FeatureDriftMetricsCollector — Feature drift metrics collector for Prometheus
 * 
 * Exports feature drift metrics from Redis cache:
 * - PSI scores per feature
 * - KS statistics per feature
 * - JS divergence per feature
 * - Combined drift scores
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class FeatureDriftMetricsCollector implements CollectorInterface
{
    private const CACHE_PREFIX = 'ml:feature_drift:reference:';

    public function register(): void
    {
        Prometheus::addGauge()
            ->name('catvrf_feature_drift_psi')
            ->help('Feature drift PSI score')
            ->label('feature', 'vertical');

        Prometheus::addGauge()
            ->name('catvrf_feature_drift_ks')
            ->help('Feature drift KS statistic')
            ->label('feature', 'vertical');

        Prometheus::addGauge()
            ->name('catvrf_feature_drift_js')
            ->help('Feature drift JS divergence')
            ->label('feature', 'vertical');

        Prometheus::addGauge()
            ->name('catvrf_feature_drift_combined')
            ->help('Feature drift combined score')
            ->label('feature', 'vertical');

        Prometheus::addCounter()
            ->name('catvrf_feature_drift_detected_total')
            ->help('Feature drift detected count')
            ->label('feature', 'vertical', 'severity');
    }

    public function collect(): void
    {
        // Collect drift metrics from Redis cache
        // In production, this would query the actual drift detection results
        // For now, we'll set default values
        
        $verticals = ['medical', 'fraud', 'recommendation'];
        $features = ['amount_log', 'hour_of_day', 'tenant_risk_score', 'user_age_days'];

        foreach ($verticals as $vertical) {
            foreach ($features as $feature) {
                // Get drift scores from Redis if available
                $psiScore = $this->getDriftScore($vertical, $feature, 'psi');
                $ksScore = $this->getDriftScore($vertical, $feature, 'ks');
                $jsScore = $this->getDriftScore($vertical, $feature, 'js');
                $combinedScore = $this->getDriftScore($vertical, $feature, 'combined');

                $featureLabel = $this->sanitizeLabel($feature);
                $verticalLabel = $this->sanitizeLabel($vertical);

                if ($psiScore !== null) {
                    Prometheus::addGauge()
                        ->name('catvrf_feature_drift_psi')
                        ->label('feature', $featureLabel)
                        ->label('vertical', $verticalLabel)
                        ->set($psiScore);
                }

                if ($ksScore !== null) {
                    Prometheus::addGauge()
                        ->name('catvrf_feature_drift_ks')
                        ->label('feature', $featureLabel)
                        ->label('vertical', $verticalLabel)
                        ->set($ksScore);
                }

                if ($jsScore !== null) {
                    Prometheus::addGauge()
                        ->name('catvrf_feature_drift_js')
                        ->label('feature', $featureLabel)
                        ->label('vertical', $verticalLabel)
                        ->set($jsScore);
                }

                if ($combinedScore !== null) {
                    Prometheus::addGauge()
                        ->name('catvrf_feature_drift_combined')
                        ->label('feature', $featureLabel)
                        ->label('vertical', $verticalLabel)
                        ->set($combinedScore);
                }
            }
        }
    }

    private function getDriftScore(string $vertical, string $feature, string $metricType): ?float
    {
        $key = self::CACHE_PREFIX . $vertical . ':' . $feature . ':' . $metricType;
        $value = Redis::connection()->get($key);
        
        return $value !== null ? (float) $value : null;
    }

    private function sanitizeLabel(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', substr($value, 0, 50));
    }
}
