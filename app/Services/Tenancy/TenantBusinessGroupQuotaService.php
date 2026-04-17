<?php declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

/**
 * Tenant Business Group Quota Service
 *
 * Production 2026 CANON - B2B Multi-Tenant Quota Management
 *
 * Manages aggregated quotas for B2B business groups (e.g., clinic networks):
 * - Aggregates individual tenant quotas into group-level limits
 * - Checks group-level quota before tenant-level quota
 * - Supports hierarchical quota sharing (group -> tenant)
 * - Provides group quota statistics and reporting
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class TenantBusinessGroupQuotaService
{
    private const GROUP_QUOTA_PREFIX = 'tenant:group:quota:';
    private const GROUP_TTL = 86400; // 24 hours

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly TenantResourceLimiterService $tenantLimiter,
    ) {}

    /**
     * Check if business group has quota for a resource
     *
     * @throws \App\Exceptions\TenantQuotaExceededException
     */
    public function checkGroupQuota(int $businessGroupId, string $resourceType, int $amount = 1): bool
    {
        $groupQuota = $this->getGroupQuota($businessGroupId, $resourceType);
        $groupUsage = $this->getGroupUsage($businessGroupId, $resourceType);

        if ($groupUsage + $amount > $groupQuota) {
            $this->logger->warning('Business group quota exceeded', [
                'business_group_id' => $businessGroupId,
                'resource_type' => $resourceType,
                'used' => $groupUsage,
                'requested' => $amount,
                'quota' => $groupQuota,
            ]);

            return false;
        }

        $this->recordGroupUsage($businessGroupId, $resourceType, $amount);
        return true;
    }

    /**
     * Get group-level quota for a resource
     */
    private function getGroupQuota(int $businessGroupId, string $resourceType): int
    {
        $key = self::GROUP_QUOTA_PREFIX . "custom:{$resourceType}:{$businessGroupId}";
        $customQuota = (int) $this->redis->connection()->get($key);

        if ($customQuota > 0) {
            return $customQuota;
        }

        // Get default group quota from config
        return match ($resourceType) {
            'ai_tokens' => 10000000, // 10M tokens/day for group
            'redis_ops' => 1000000, // 1M ops/hour for group
            'db_queries' => 500000, // 500K queries/hour for group
            'storage_bytes' => 100 * 1024 * 1024 * 1024, // 100GB/day for group
            default => PHP_INT_MAX,
        };
    }

    /**
     * Get current group usage for a resource
     */
    private function getGroupUsage(int $businessGroupId, string $resourceType): int
    {
        $key = self::GROUP_QUOTA_PREFIX . "{$resourceType}:{$businessGroupId}";
        return (int) $this->redis->connection()->get($key) ?: 0;
    }

    /**
     * Record group resource usage
     */
    private function recordGroupUsage(int $businessGroupId, string $resourceType, int $amount): void
    {
        $key = self::GROUP_QUOTA_PREFIX . "{$resourceType}:{$businessGroupId}";
        $this->redis->connection()->incrby($key, $amount);
        $this->redis->connection()->expire($key, self::GROUP_TTL);
    }

    /**
     * Set custom group quota
     */
    public function setGroupQuota(int $businessGroupId, string $resourceType, int $quota): void
    {
        $key = self::GROUP_QUOTA_PREFIX . "custom:{$resourceType}:{$businessGroupId}";
        $this->redis->connection()->set($key, $quota);

        $this->logger->info('Business group quota set', [
            'business_group_id' => $businessGroupId,
            'resource_type' => $resourceType,
            'quota' => $quota,
        ]);
    }

    /**
     * Get all tenants in a business group
     */
    public function getGroupTenants(int $businessGroupId): array
    {
        return Tenant::where('business_group_id', $businessGroupId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get aggregated quota statistics for a business group
     */
    public function getGroupQuotaStats(int $businessGroupId): array
    {
        $resources = ['ai_tokens', 'redis_ops', 'db_queries', 'storage_bytes'];
        $stats = [];
        $tenantIds = $this->getGroupTenants($businessGroupId);

        foreach ($resources as $resource) {
            $groupQuota = $this->getGroupQuota($businessGroupId, $resource);
            $groupUsage = $this->getGroupUsage($businessGroupId, $resource);

            // Aggregate individual tenant usage
            $tenantUsage = 0;
            foreach ($tenantIds as $tenantId) {
                $tenantUsage += $this->tenantLimiter->getUsage($resource, $tenantId);
            }

            $stats[$resource] = [
                'group_quota' => $groupQuota,
                'group_usage' => $groupUsage,
                'tenant_usage' => $tenantUsage,
                'total_usage' => $groupUsage + $tenantUsage,
                'percentage' => $groupQuota > 0 ? round((($groupUsage + $tenantUsage) / $groupQuota) * 100, 2) : 0,
                'remaining' => max(0, $groupQuota - $groupUsage - $tenantUsage),
                'tenant_count' => count($tenantIds),
            ];
        }

        return $stats;
    }

    /**
     * Reset group usage for a resource
     */
    public function resetGroupUsage(int $businessGroupId, ?string $resourceType = null): void
    {
        $resources = $resourceType ? [$resourceType] : ['ai_tokens', 'redis_ops', 'db_queries', 'storage_bytes'];

        foreach ($resources as $resource) {
            $key = self::GROUP_QUOTA_PREFIX . "{$resource}:{$businessGroupId}";
            $this->redis->connection()->del($key);
        }

        $this->logger->info('Business group quota usage reset', [
            'business_group_id' => $businessGroupId,
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * Check both group and tenant quota (group-first strategy)
     *
     * @throws \App\Exceptions\TenantQuotaExceededException
     */
    public function checkQuotaWithGroup(int $tenantId, int $businessGroupId, string $resourceType, int $amount = 1): void
    {
        // First check group quota
        if ($businessGroupId > 0) {
            if (!$this->checkGroupQuota($businessGroupId, $resourceType, $amount)) {
                throw new \App\Exceptions\TenantQuotaExceededException(
                    $tenantId,
                    "group_{$resourceType}",
                    $this->getGroupUsage($businessGroupId, $resourceType),
                    $this->getGroupQuota($businessGroupId, $resourceType),
                    $amount
                );
            }
        }

        // Then check tenant quota
        $this->tenantLimiter->checkVerticalQuota($tenantId, 'default', $resourceType, $amount);
    }

    /**
     * Get quota plan for business group
     */
    public function getGroupPlan(int $businessGroupId): string
    {
        $group = $this->db->table('business_groups')->where('id', $businessGroupId)->first();
        
        if (!$group) {
            return 'free';
        }

        $meta = is_string($group->meta) ? json_decode($group->meta, true) : $group->meta;
        return $meta['quota_plan'] ?? 'free';
    }

    /**
     * Apply quota plan to business group
     */
    public function applyGroupPlan(int $businessGroupId, string $plan): void
    {
        $quotas = match($plan) {
            'enterprise' => [
                'ai_tokens' => PHP_INT_MAX,
                'redis_ops' => PHP_INT_MAX,
                'db_queries' => PHP_INT_MAX,
                'storage_bytes' => PHP_INT_MAX,
            ],
            'pro' => [
                'ai_tokens' => 10000000,
                'redis_ops' => 1000000,
                'db_queries' => 500000,
                'storage_bytes' => 100 * 1024 * 1024 * 1024,
            ],
            'starter' => [
                'ai_tokens' => 1000000,
                'redis_ops' => 100000,
                'db_queries' => 50000,
                'storage_bytes' => 10 * 1024 * 1024 * 1024,
            ],
            default => [
                'ai_tokens' => 100000,
                'redis_ops' => 10000,
                'db_queries' => 5000,
                'storage_bytes' => 1024 * 1024 * 1024,
            ],
        };

        foreach ($quotas as $resource => $quota) {
            $this->setGroupQuota($businessGroupId, $resource, $quota);
        }

        $this->logger->info('Business group quota plan applied', [
            'business_group_id' => $businessGroupId,
            'plan' => $plan,
            'quotas' => $quotas,
        ]);
    }
}
