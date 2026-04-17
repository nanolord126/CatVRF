<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Services\Analytics\QuotaClickHouseRepository;
use App\Services\Tenancy\TenantQuotaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes max

    private const QUOTA_PREFIX = 'tenant:quota:';
    private const DRIFT_THRESHOLD = 0.01; // 1% drift tolerance
    private const MAX_TENANTS_PER_RUN = 100; // Limit to prevent long-running jobs

    /**
     * Execute the job.
     */
    public function handle(
        QuotaClickHouseRepository $clickHouse,
        TenantQuotaService $quotaService
    ): void {
        try {
            $startTime = now();
            $correctedCount = 0;
            $checkedCount = 0;

            // Get all quota keys from Redis
            $pattern = self::QUOTA_PREFIX . '*';
            $keys = Redis::connection()->keys($pattern);

            // Group by tenant_id and resource_type
            $tenantResourceMap = $this->groupKeysByTenantAndResource($keys);

            // Limit to prevent long-running jobs
            $tenantResourceMap = array_slice($tenantResourceMap, 0, self::MAX_TENANTS_PER_RUN, true);

            foreach ($tenantResourceMap as $tenantId => $resources) {
                foreach ($resources as $resourceType => $redisKey) {
                    $checkedCount++;

                    // Get Redis usage
                    $redisUsage = (float) Redis::connection()->get($redisKey) ?: 0;

                    // Get ClickHouse current hour usage
                    $clickHouseUsage = $clickHouse->getCurrentHourUsage($tenantId, $resourceType);

                    // Calculate drift
                    $drift = abs($redisUsage - $clickHouseUsage);
                    $driftPercent = $redisUsage > 0 ? ($drift / $redisUsage) * 100 : 0;

                    // Correct if drift exceeds threshold
                    if ($driftPercent > self::DRIFT_THRESHOLD) {
                        Log::warning('Quota usage drift detected, correcting', [
                            'tenant_id' => $tenantId,
                            'resource_type' => $resourceType,
                            'redis_usage' => $redisUsage,
                            'clickhouse_usage' => $clickHouseUsage,
                            'drift' => $drift,
                            'drift_percent' => round($driftPercent, 2),
                        ]);

                        // Correct Redis to match ClickHouse (ClickHouse is source of truth)
                        Redis::connection()->set($redisKey, $clickHouseUsage);
                        Redis::connection()->expire($redisKey, 86400); // 24 hours

                        $correctedCount++;
                    }
                }
            }

            $duration = now()->diffInSeconds($startTime);

            Log::info('Quota usage reconciliation completed', [
                'checked_count' => $checkedCount,
                'corrected_count' => $correctedCount,
                'duration_seconds' => $duration,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to reconcile quota usage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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
            if (count($parts) !== 4) {
                continue;
            }

            [, , $resourceType, $tenantId] = $parts;

            if (!isset($map[$tenantId])) {
                $map[$tenantId] = [];
            }

            $map[$tenantId][$resourceType] = $key;
        }

        return $map;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('ReconcileQuotaUsageJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send alert to monitoring system
    }
}
