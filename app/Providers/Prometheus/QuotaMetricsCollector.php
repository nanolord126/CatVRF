<?php declare(strict_types=1);

namespace App\Providers\Prometheus;

use Spatie\Prometheus\CollectorInterface;
use Spatie\Prometheus\Facades\Prometheus;
use Illuminate\Support\Facades\Redis;

/**
 * QuotaMetricsCollector — Quota metrics collector for Prometheus
 * 
 * Exports quota usage metrics from Redis:
 * - Current usage per tenant and resource type
 * - Usage ratios
 * - Quota limits
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class QuotaMetricsCollector implements CollectorInterface
{
    private const QUOTA_PREFIX = 'tenant:quota:';

    public function register(): void
    {
        Prometheus::addGauge()
            ->name('catvrf_quota_usage_current')
            ->help('Current quota usage')
            ->label('resource_type');

        Prometheus::addGauge()
            ->name('catvrf_quota_usage_ratio')
            ->help('Quota usage ratio (current / limit)')
            ->label('resource_type');
    }

    public function collect(): void
    {
        $resourceTypes = ['ai_tokens', 'llm_requests', 'slot_holds', 'ml_retrain'];
        $limits = [
            'ai_tokens' => 100000,
            'llm_requests' => 10000,
            'slot_holds' => 1000,
            'ml_retrain' => 100,
        ];

        foreach ($resourceTypes as $resourceType) {
            // Sum usage across all tenants for aggregate metrics
            $pattern = self::QUOTA_PREFIX . $resourceType . ':*';
            $keys = Redis::connection()->keys($pattern);
            
            $totalUsage = 0.0;
            foreach ($keys as $key) {
                $value = Redis::connection()->get($key);
                if ($value !== null) {
                    $totalUsage += (float) $value;
                }
            }

            $resourceLabel = $this->sanitizeLabel($resourceType);
            $limit = $limits[$resourceType] ?? 10000;
            $usageRatio = min($totalUsage / max($limit, 1), 1.0);

            Prometheus::addGauge()
                ->name('catvrf_quota_usage_current')
                ->label('resource_type', $resourceLabel)
                ->set($totalUsage);

            Prometheus::addGauge()
                ->name('catvrf_quota_usage_ratio')
                ->label('resource_type', $resourceLabel)
                ->set($usageRatio);
        }
    }

    private function sanitizeLabel(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', substr($value, 0, 50));
    }
}
