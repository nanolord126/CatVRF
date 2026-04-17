<?php declare(strict_types=1);

namespace App\Services\Tenancy;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use OpenTelemetry\API\Metrics\Counter;
use OpenTelemetry\API\Metrics\Gauge;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\Histogram;

/**
 * Tenant Metrics Exporter
 * 
 * Exports OpenTelemetry metrics for tenant observability:
 * - Active tenants count
 * - Queries per tenant
 * - AI cost per tenant
 * - Fraud rate per tenant
 * - Resource usage per tenant
 */
final readonly class TenantMetricsExporter
{
    private const METRICS_PREFIX = 'tenant_';

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
    ) {}

    /**
     * Export all tenant metrics in Prometheus format
     */
    public function exportPrometheusMetrics(): string
    {
        $lines = [];

        // Active tenants
        $lines[] = $this->formatGauge(
            'active_tenants',
            $this->getActiveTenantsCount(),
            'Number of active tenants'
        );

        // Total tenants
        $lines[] = $this->formatGauge(
            'total_tenants',
            $this->getTotalTenantsCount(),
            'Total number of tenants'
        );

        // Queries per tenant (top 10)
        foreach ($this->getTopTenantsByQueries(10) as $tenantId => $count) {
            $lines[] = $this->formatCounter(
                'tenant_queries_total',
                $count,
                'Total DB queries per tenant',
                ['tenant_id' => (string) $tenantId]
            );
        }

        // AI cost per tenant
        foreach ($this->getAICostPerTenant() as $tenantId => $cost) {
            $lines[] = $this->formatGauge(
                'tenant_ai_cost_rubles',
                $cost,
                'AI cost in rubles per tenant',
                ['tenant_id' => (string) $tenantId]
            );
        }

        // Fraud rate per tenant
        foreach ($this->getFraudRatePerTenant() as $tenantId => $rate) {
            $lines[] = $this->formatGauge(
                'tenant_fraud_rate',
                $rate,
                'Fraud detection rate per tenant (0-1)',
                ['tenant_id' => (string) $tenantId]
            );
        }

        // Resource usage per tenant
        foreach ($this->getResourceUsagePerTenant() as $tenantId => $usage) {
            $lines[] = $this->formatGauge(
                'tenant_ai_tokens_used',
                $usage['ai_tokens'] ?? 0,
                'AI tokens used by tenant',
                ['tenant_id' => (string) $tenantId]
            );
            
            $lines[] = $this->formatGauge(
                'tenant_redis_ops',
                $usage['redis_ops'] ?? 0,
                'Redis operations by tenant',
                ['tenant_id' => (string) $tenantId]
            );
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Get active tenants count
     */
    private function getActiveTenantsCount(): int
    {
        return $this->db->table('tenants')
            ->where('is_active', true)
            ->count();
    }

    /**
     * Get total tenants count
     */
    private function getTotalTenantsCount(): int
    {
        return $this->db->table('tenants')->count();
    }

    /**
     * Get top tenants by query count
     */
    private function getTopTenantsByQueries(int $limit): array
    {
        // This would be tracked by a query logger
        // For now, return empty array (implement query logging separately)
        return [];
    }

    /**
     * Get AI cost per tenant
     */
    private function getAICostPerTenant(): array
    {
        $costs = [];
        
        // Get from Redis cache
        $pattern = 'tenant:quota:ai_tokens:*';
        $keys = $this->redis->connection()->keys($pattern);

        foreach ($keys as $key) {
            $tenantId = str_replace('tenant:quota:ai_tokens:', '', $key);
            $tokensUsed = (int) $this->redis->connection()->get($key) ?: 0;
            
            // Assume 0.1 ruble per 1000 tokens
            $costs[$tenantId] = ($tokensUsed / 1000) * 0.1;
        }

        return $costs;
    }

    /**
     * Get fraud rate per tenant
     */
    private function getFraudRatePerTenant(): array
    {
        $rates = [];
        
        $results = $this->db->table('fraud_attempts')
            ->selectRaw('tenant_id, COUNT(*) as total, SUM(CASE WHEN decision = "block" THEN 1 ELSE 0 END) as blocked')
            ->groupBy('tenant_id')
            ->get();

        foreach ($results as $result) {
            $rate = $result->total > 0 ? ($result->blocked / $result->total) : 0;
            $rates[$result->tenant_id] = round($rate, 4);
        }

        return $rates;
    }

    /**
     * Get resource usage per tenant
     */
    private function getResourceUsagePerTenant(): array
    {
        $usage = [];
        $pattern = 'tenant:quota:*';
        $keys = $this->redis->connection()->keys($pattern);

        foreach ($keys as $key) {
            if (str_contains($key, 'custom:')) {
                continue; // Skip custom quota keys
            }

            $parts = explode(':', $key);
            if (count($parts) === 3) {
                [$prefix, $resource, $tenantId] = $parts;
                $value = (int) $this->redis->connection()->get($key) ?: 0;
                
                if (!isset($usage[$tenantId])) {
                    $usage[$tenantId] = [];
                }
                
                $usage[$tenantId][$resource] = $value;
            }
        }

        return $usage;
    }

    /**
     * Record tenant metric
     */
    public function recordMetric(string $metricName, string $tenantId, float $value, array $labels = []): void
    {
        $key = self::METRICS_PREFIX . $metricName . ':' . $tenantId;
        
        if (!empty($labels)) {
            $key .= ':' . md5(json_encode($labels));
        }

        $this->redis->connection()->incrbyfloat($key, $value);
        $this->redis->connection()->expire($key, 86400); // 24 hours
    }

    /**
     * Get tenant health status
     */
    public function getTenantHealth(string $tenantId): array
    {
        $limiter = app(TenantResourceLimiterService::class);
        $quotaStats = $limiter->getQuotaStats((int) $tenantId);

        return [
            'tenant_id' => $tenantId,
            'is_active' => $this->isTenantActive($tenantId),
            'quotas' => $quotaStats,
            'health_score' => $this->calculateHealthScore($quotaStats),
            'alerts' => $this->generateAlerts($quotaStats),
        ];
    }

    /**
     * Check if tenant is active
     */
    private function isTenantActive(string $tenantId): bool
    {
        return (bool) $this->db->table('tenants')
            ->where('id', $tenantId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Calculate health score (0-100)
     */
    private function calculateHealthScore(array $quotaStats): int
    {
        if (empty($quotaStats)) {
            return 100;
        }

        $totalPercentage = 0;
        $count = 0;

        foreach ($quotaStats as $resource => $stats) {
            $totalPercentage += $stats['percentage'];
            $count++;
        }

        $avgPercentage = $count > 0 ? ($totalPercentage / $count) : 0;

        // Health score decreases as quota usage increases
        return max(0, 100 - (int) $avgPercentage);
    }

    /**
     * Generate alerts based on quota usage
     */
    private function generateAlerts(array $quotaStats): array
    {
        $alerts = [];

        foreach ($quotaStats as $resource => $stats) {
            if ($stats['percentage'] >= 90) {
                $alerts[] = [
                    'severity' => 'critical',
                    'resource' => $resource,
                    'message' => "{$resource} quota at {$stats['percentage']}%",
                ];
            } elseif ($stats['percentage'] >= 75) {
                $alerts[] = [
                    'severity' => 'warning',
                    'resource' => $resource,
                    'message' => "{$resource} quota at {$stats['percentage']}%",
                ];
            }
        }

        return $alerts;
    }

    private function formatGauge(string $name, float $value, string $help, array $labels = []): string
    {
        $labelStr = $this->formatLabels($labels);
        return "# HELP {$name} {$help}\n# TYPE {$name} gauge\n{$name}{$labelStr} {$value}";
    }

    private function formatCounter(string $name, float $value, string $help, array $labels = []): string
    {
        $labelStr = $this->formatLabels($labels);
        return "# HELP {$name} {$help}\n# TYPE {$name} counter\n{$name}{$labelStr} {$value}";
    }

    private function formatLabels(array $labels): string
    {
        if (empty($labels)) {
            return '';
        }

        $parts = [];
        foreach ($labels as $key => $value) {
            $parts[] = "{$key}=\"{$value}\"";
        }

        return '{' . implode(',', $parts) . '}';
    }
}
