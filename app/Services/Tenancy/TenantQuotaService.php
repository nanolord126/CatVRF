<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Jobs\Analytics\SyncQuotaUsageToClickHouseJob;
use App\Services\Analytics\QuotaClickHouseRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * Tenant Quota Service
 * 
 * Production 2026 CANON - Redis + ClickHouse Hybrid Quota Management
 * 
 * Architecture:
 * - Redis: Fast quota checks and increments (hot path, sub-millisecond)
 * - ClickHouse: Long-term analytics, billing, historical reporting (async)
 * - Async sync via queue to avoid blocking operations
 * - Idempotent inserts with quota_event_id
 * - OpenTelemetry trace_id propagation
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class TenantQuotaService
{
    use DispatchesJobs;

    private const QUOTA_PREFIX = 'tenant:quota:';
    private const QUOTA_TTL = 86400; // 24 hours

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly Queue $queue,
        private readonly QuotaClickHouseRepository $clickHouse,
        private readonly LogManager $logger,
    ) {}

    /**
     * Increment quota usage with async ClickHouse sync
     * 
     * This is the main entry point for quota operations.
     * 
     * Flow:
     * 1. Fraud check (via TenantResourceLimiterService)
     * 2. Redis increment (fast, synchronous)
     * 3. Dispatch async job to ClickHouse (non-blocking)
     * 
     * @param int $tenantId Tenant ID
     * @param string $resourceType Resource type (ai_tokens, llm_requests, slot_holds, etc.)
     * @param float $amount Amount to increment
     * @param array $context Additional context (vertical_code, user_id, correlation_id, etc.)
     * @return bool Success
     */
    public function incrementUsage(
        int $tenantId,
        string $resourceType,
        float $amount,
        array $context = []
    ): bool {
        // Fraud check should be done before calling this method
        // via TenantResourceLimiterService or FraudControlService

        try {
            // Generate unique event ID for idempotency
            $quotaEventId = $context['quota_event_id'] ?? Uuid::uuid4()->toString();

            // Redis increment (synchronous, fast)
            $redisKey = $this->getRedisKey($tenantId, $resourceType);
            $this->redis->connection()->incrbyfloat($redisKey, $amount);
            $this->redis->connection()->expire($redisKey, self::QUOTA_TTL);

            // Prepare ClickHouse event data
            $clickHouseEvent = [
                'quota_event_id' => $quotaEventId,
                'tenant_id' => $tenantId,
                'business_group_id' => $context['business_group_id'] ?? 0,
                'vertical_code' => $context['vertical_code'] ?? 'unknown',
                'resource_type' => $resourceType,
                'operation_type' => $context['operation_type'] ?? 'increment',
                'amount_used' => $amount,
                'unit' => $context['unit'] ?? 'count',
                'event_timestamp' => $context['event_timestamp'] ?? now()->toDateTimeString(),
                'user_id' => $context['user_id'] ?? 0,
                'correlation_id' => $context['correlation_id'] ?? null,
                'trace_id' => $context['trace_id'] ?? null,
                'metadata' => $context['metadata'] ?? null,
            ];

            // Dispatch async job to ClickHouse (non-blocking)
            $this->queue->push(new SyncQuotaUsageToClickHouseJob($clickHouseEvent));

            $this->logger->debug('Quota usage incremented', [
                'quota_event_id' => $quotaEventId,
                'tenant_id' => $tenantId,
                'resource_type' => $resourceType,
                'amount' => $amount,
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to increment quota usage', [
                'tenant_id' => $tenantId,
                'resource_type' => $resourceType,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Get current usage from Redis
     */
    public function getCurrentUsage(int $tenantId, string $resourceType): float
    {
        $redisKey = $this->getRedisKey($tenantId, $resourceType);
        return (float) ($this->redis->connection()->get($redisKey) ?: 0);
    }

    /**
     * Get current hour usage from ClickHouse
     */
    public function getCurrentHourUsage(int $tenantId, string $resourceType): float
    {
        return $this->clickHouse->getCurrentHourUsage($tenantId, $resourceType);
    }

    /**
     * Get daily usage from ClickHouse
     */
    public function getDailyUsage(int $tenantId, string $resourceType, ?string $date = null): float
    {
        return $this->clickHouse->getDailyUsage($tenantId, $resourceType, $date);
    }

    /**
     * Get usage for date range from ClickHouse
     */
    public function getUsageInRange(
        int $tenantId,
        string $resourceType,
        string $startDate,
        string $endDate
    ): float {
        return $this->clickHouse->getUsageInRange($tenantId, $resourceType, $startDate, $endDate);
    }

    /**
     * Check if tenant has enough quota
     * 
     * Uses Redis for fast check, but can also validate against ClickHouse
     * for more accurate long-term usage tracking.
     */
    public function checkQuota(
        int $tenantId,
        string $resourceType,
        float $amount,
        int $limit,
        bool $useClickHouse = false
    ): bool {
        $currentUsage = $this->getCurrentUsage($tenantId, $resourceType);

        if ($useClickHouse) {
            // Include ClickHouse usage for more accurate check
            $clickHouseUsage = $this->getCurrentHourUsage($tenantId, $resourceType);
            $currentUsage += $clickHouseUsage;
        }

        return $currentUsage + $amount <= $limit;
    }

    /**
     * Reset quota usage in Redis
     */
    public function resetUsage(int $tenantId, ?string $resourceType = null): void
    {
        if ($resourceType) {
            $redisKey = $this->getRedisKey($tenantId, $resourceType);
            $this->redis->connection()->del($redisKey);
        } else {
            // Reset all quota keys for tenant
            $pattern = $this->getRedisKey($tenantId, '*');
            $keys = $this->redis->connection()->keys($pattern);
            
            if (!empty($keys)) {
                $this->redis->connection()->del(...$keys);
            }
        }

        $this->logger->info('Quota usage reset', [
            'tenant_id' => $tenantId,
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * Get quota statistics for tenant
     */
    public function getQuotaStats(int $tenantId, array $resourceTypes): array
    {
        $stats = [];

        foreach ($resourceTypes as $resourceType) {
            $stats[$resourceType] = [
                'current_usage' => $this->getCurrentUsage($tenantId, $resourceType),
                'hourly_usage' => $this->getCurrentHourUsage($tenantId, $resourceType),
                'daily_usage' => $this->getDailyUsage($tenantId, $resourceType),
            ];
        }

        return $stats;
    }

    /**
     * Batch increment quota usage (for bulk operations)
     */
    public function batchIncrementUsage(array $events): int
    {
        $successCount = 0;

        foreach ($events as $event) {
            $success = $this->incrementUsage(
                $event['tenant_id'],
                $event['resource_type'],
                $event['amount'],
                $event['context'] ?? []
            );

            if ($success) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Get Redis key for quota
     */
    private function getRedisKey(int $tenantId, string $resourceType): string
    {
        return self::QUOTA_PREFIX . "{$resourceType}:{$tenantId}";
    }

    /**
     * Test ClickHouse connection
     */
    public function testClickHouseConnection(): bool
    {
        return $this->clickHouse->testConnection();
    }
}
