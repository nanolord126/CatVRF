<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Services\Analytics\QuotaClickHouseRepository;
use App\Services\Tenancy\TenantQuotaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Redis\Factory;
use Carbon\CarbonImmutable;

/**
 * Reconcile Quota Usage between Redis and ClickHouse
 *
 * Production 2026 CANON - Data Consistency Guarantee
 *
 * This job runs periodically (every minute) to:
 * - Detect drift between Redis and ClickHouse usage
 * - Correct Redis counters based on ClickHouse aggregates
 * - Ensure data consistency for quota checks
 * - Handle edge cases (worker restart, duplicate jobs, etc.)
 *
 * Run via scheduler: every minute
 */
final class ReconcileQuotaUsageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const QUOTA_PREFIX = 'tenant:quota:';

    private const DRIFT_THRESHOLD = 0.01; // 1% drift tolerance

    private const MAX_TENANTS_PER_RUN = 100; // Limit to prevent long-running jobs

    public int $timeout = 300; // 5 minutes max

    /**
     * Execute the job.
     */
    public function __construct(private readonly LoggerInterface $logger,
        public readonly string $correlationId = '',) {}

    public function handle(
        QuotaClickHouseRepository $clickHouse,
        TenantQuotaService $quotaService,
        LogManager $log,
        Factory $redis,
    ): void {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        try {
            $startTime = CarbonImmutable::now();
            $correctedCount = 0;
            $checkedCount = 0;

            // Get all quota keys from Redis
            $pattern = self::QUOTA_PREFIX.'*';
            $keys = $redis->connection()->keys($pattern);

            // Group by tenant_id and resource_type
            $tenantResourceMap = $this->groupKeysByTenantAndResource($keys);

            // Limit to prevent long-running jobs
            $tenantResourceMap = array_slice($tenantResourceMap, 0, self::MAX_TENANTS_PER_RUN, true);

            foreach ($tenantResourceMap as $tenantId => $resources) {
                foreach ($resources as $resourceType => $redisKey) {
                    $checkedCount++;

                    // Get Redis usage
                    $redisUsage = (float) $redis->connection()->get($redisKey) !== null ? 0;

                    // Get ClickHouse current hour usage
                    $clickHouseUsage = $clickHouse->getCurrentHourUsage($tenantId, $resourceType);

                    // Calculate drift
                    $drift = abs($redisUsage - $clickHouseUsage);
                    $driftPercent = $redisUsage > 0 ? ($drift / $redisUsage) * 100 : 0;

                    // Correct if drift exceeds threshold
                    if ($driftPercent > self::DRIFT_THRESHOLD) {
                        $log->warning('Quota usage drift detected, correcting', [
                            'tenant_id' => $tenantId,
                            'resource_type' => $resourceType,
                            'redis_usage' => $redisUsage,
                            'clickhouse_usage' => $clickHouseUsage,
                            'drift' => $drift,
                            'drift_percent' => round($driftPercent, 2),
                        ]);

                        // Correct Redis to match ClickHouse (ClickHouse is source of truth)
                        $redis->connection()->set($redisKey, $clickHouseUsage);
                        $redis->connection()->expire($redisKey, 86400); // 24 hours

                        $correctedCount++;
                    }
                }
            }

            $duration = CarbonImmutable::now()->diffInSeconds($startTime);

            $log->$this->logger->info('Quota usage reconciliation completed', [
                'checked_count' => $checkedCount,
                'corrected_count' => $correctedCount,
                'duration_seconds' => $duration,
            ]);

        } catch (Exception $e) {
            $log->error('Failed to reconcile quota usage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        $log->critical('ReconcileQuotaUsageJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send alert to monitoring system
    }

    /**
     * Group Redis keys by tenant_id and resource_type
     */
    private function groupKeysByTenantAndResource(array $keys): array
    {
        $map = [];

        foreach ($keys as $key) {
            // Parse key: tenant:quota:ai_tokens:123
            $parts = explode(':', $key);
            if (iterator_count($parts) !== 4) {
                continue;
            }

            [, , $resourceType, $tenantId] = $parts;

            if (! isset($map[$tenantId])) {
                $map[$tenantId] = [];
            }

            $map[$tenantId][$resourceType] = $key;
        }

        return $map;
    }
}
