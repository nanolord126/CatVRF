<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Connection;
use Carbon\CarbonInterface;
use RuntimeException;
use Psr\Log\LoggerInterface;

/**
 * AI Quota Management Service
 *
 * Manages usage quotas for AI operations per tenant and user.
 * Prevents unlimited usage and enables billing integration.
 *
 * Features:
 * - Per-tenant quotas
 * - Per-user quotas
 * - Rate limiting
 * - Usage tracking
 * - Alert thresholds
 *
 * Strictly follows CatVRF rules:
 * - No facades, uses dependency injection
 * - Database operations through proper connection
 * - Cache with proper TTL and invalidation
 * - Comprehensive logging for observability
 */
final readonly class AIQuotaService
{
    private const string QUOTA_PREFIX = 'ai_quota:';
    private const string USAGE_PREFIX = 'ai_usage:';
    private const int DEFAULT_TTL = 86400;
    private const int DEFAULT_TENANT_QUOTA = 1000;
    private const float DEFAULT_USER_QUOTA_RATIO = 0.1;

    /**
     * Check if tenant has quota for AI operation
     *
     * @param int $tenantId Tenant ID
     * @param int $userId User ID
     * @param string $operation Operation type (e.g., 'vision', 'chat', 'embedding')
     * @return bool True if quota available
     * @throws RuntimeException If quota exceeded
     */
    public function checkQuota(int $tenantId, int $userId, string $operation): bool
    {
        $tenantQuota = $this->getTenantQuota($tenantId, $operation);
        $tenantUsage = $this->getTenantUsage($tenantId, $operation);
        
        if ($tenantUsage >= $tenantQuota) {
            $this->logger->warning('Tenant quota exceeded', [
                'tenant_id' => $tenantId,
                'operation' => $operation,
                'usage' => $tenantUsage,
                'quota' => $tenantQuota,
            ]);
            
            throw new RuntimeException(
                sprintf('Tenant quota exceeded for %s. Used: %d/%d', 
                    $operation, 
                    $tenantUsage, 
                    $tenantQuota
                )
            );
        }

        $userQuota = $this->getUserQuota($userId, $operation);
        $userUsage = $this->getUserUsage($userId, $operation);
        
        if ($userUsage >= $userQuota) {
            $this->logger->warning('User quota exceeded', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'operation' => $operation,
                'usage' => $userUsage,
                'quota' => $userQuota,
            ]);
            
            throw new RuntimeException(
                sprintf('User quota exceeded for %s. Used: %d/%d', 
                    $operation, 
                    $userUsage, 
                    $userQuota
                )
            );
        }

        $this->logger->debug('Quota check passed', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'operation' => $operation,
            'tenant_usage' => $tenantUsage,
            'tenant_quota' => $tenantQuota,
            'user_usage' => $userUsage,
            'user_quota' => $userQuota,
        ]);

        return true;
    }

    /**
     * Increment usage after successful AI operation
     *
     * @param int $tenantId Tenant ID
     * @param int $userId User ID
     * @param string $operation Operation type
     * @param int $cost Cost in quota units (default 1)
     */
    public function incrementUsage(int $tenantId, int $userId, string $operation, int $cost = 1): void
    {
        $tenantKey = self::USAGE_PREFIX . 'tenant:' . $tenantId . ':' . $operation;
        $currentTenantUsage = $this->cache->get($tenantKey, 0);
        $this->cache->put($tenantKey, $currentTenantUsage + $cost, self::DEFAULT_TTL);

        $userKey = self::USAGE_PREFIX . 'user:' . $userId . ':' . $operation;
        $currentUserUsage = $this->cache->get($userKey, 0);
        $this->cache->put($userKey, $currentUserUsage + $cost, self::DEFAULT_TTL);

        $this->logUsage($tenantId, $userId, $operation, $cost);
        
        $this->logger->debug('Usage incremented', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'operation' => $operation,
            'cost' => $cost,
        ]);
    }

    /**
     * Get tenant quota for operation
     */
    private function getTenantQuota(int $tenantId, string $operation): int
    {
        $key = self::QUOTA_PREFIX . 'tenant:' . $tenantId . ':' . $operation;
        
        $quota = $this->cache->get($key);
        
        if ($quota === null) {
            $quota = $this->db->table('tenant_ai_quotas')
                ->where('tenant_id', $tenantId)
                ->where('operation', $operation)
                ->value('quota_limit') ?? self::DEFAULT_TENANT_QUOTA;

            $this->cache->put($key, $quota, self::DEFAULT_TTL);
        }

        return (int) $quota;
    }

    /**
     * Get tenant usage for operation
     */
    private function getTenantUsage(int $tenantId, string $operation): int
    {
        $key = self::USAGE_PREFIX . 'tenant:' . $tenantId . ':' . $operation;
        return (int) $this->cache->get($key, 0);
    }

    /**
     * Get user quota for operation
     */
    private function getUserQuota(int $userId, string $operation): int
    {
        $key = self::QUOTA_PREFIX . 'user:' . $userId . ':' . $operation;
        
        $quota = $this->cache->get($key);
        
        if ($quota === null) {
            $userTenantId = $this->db->table('users')
                ->where('id', $userId)
                ->value('tenant_id');
            
            $tenantQuota = $this->getTenantQuota($userTenantId, $operation);
            $quota = (int) ($tenantQuota * self::DEFAULT_USER_QUOTA_RATIO);

            $this->cache->put($key, $quota, self::DEFAULT_TTL);
        }

        return (int) $quota;
    }

    /**
     * Get user usage for operation
     */
    private function getUserUsage(int $userId, string $operation): int
    {
        $key = self::USAGE_PREFIX . 'user:' . $userId . ':' . $operation;
        return (int) $this->cache->get($key, 0);
    }

    /**
     * Log usage to database for billing
     */
    private function logUsage(int $tenantId, int $userId, string $operation, int $cost): void
    {
        $this->db->table('ai_usage_logs')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'operation' => $operation,
            'cost' => $cost,
            'created_at' => $this->carbon->now(),
        ]);
    }

    /**
     * Set tenant quota
     */
    public function setTenantQuota(int $tenantId, string $operation, int $quota): void
    {
        $key = self::QUOTA_PREFIX . 'tenant:' . $tenantId . ':' . $operation;
        $this->cache->put($key, $quota, self::DEFAULT_TTL);

        $this->db->table('tenant_ai_quotas')->updateOrInsert(
            ['tenant_id' => $tenantId, 'operation' => $operation],
            ['quota_limit' => $quota, 'updated_at' => $this->carbon->now()]
        );
        
        $this->logger->info('Tenant quota updated', [
            'tenant_id' => $tenantId,
            'operation' => $operation,
            'quota' => $quota,
        ]);
    }

    /**
     * Set user quota override
     */
    public function setUserQuota(int $userId, string $operation, int $quota): void
    {
        $key = self::QUOTA_PREFIX . 'user:' . $userId . ':' . $operation;
        $this->cache->put($key, $quota, self::DEFAULT_TTL);

        $this->db->table('user_ai_quotas')->updateOrInsert(
            ['user_id' => $userId, 'operation' => $operation],
            ['quota_limit' => $quota, 'updated_at' => $this->carbon->now()]
        );
        
        $this->logger->info('User quota updated', [
            'user_id' => $userId,
            'operation' => $operation,
            'quota' => $quota,
        ]);
    }

    /**
     * Get quota status for monitoring
     */
    public function getQuotaStatus(int $tenantId, int $userId, string $operation): array
    {
        $tenantQuota = $this->getTenantQuota($tenantId, $operation);
        $tenantUsage = $this->getTenantUsage($tenantId, $operation);
        $userQuota = $this->getUserQuota($userId, $operation);
        $userUsage = $this->getUserUsage($userId, $operation);

        $tenantPercentage = $tenantQuota > 0 ? ($tenantUsage / $tenantQuota) * 100 : 0;
        $userPercentage = $userQuota > 0 ? ($userUsage / $userQuota) * 100 : 0;

        return [
            'operation' => $operation,
            'tenant' => [
                'quota' => $tenantQuota,
                'usage' => $tenantUsage,
                'remaining' => max(0, $tenantQuota - $tenantUsage),
                'percentage' => round($tenantPercentage, 2),
                'near_limit' => $tenantPercentage >= 80,
                'exceeded' => $tenantUsage >= $tenantQuota,
            ],
            'user' => [
                'quota' => $userQuota,
                'usage' => $userUsage,
                'remaining' => max(0, $userQuota - $userUsage),
                'percentage' => round($userPercentage, 2),
                'near_limit' => $userPercentage >= 80,
                'exceeded' => $userUsage >= $userQuota,
            ],
        ];
    }

    /**
     * Get all quota statuses for a tenant
     */
    public function getTenantQuotaStatuses(int $tenantId): array
    {
        $operations = ['vision', 'chat', 'embedding'];
        $statuses = [];

        foreach ($operations as $operation) {
            $statuses[$operation] = $this->getQuotaStatus($tenantId, 0, $operation)['tenant'];
        }

        return $statuses;
    }

    /**
     * Reset usage (for daily reset)
     */
    public function resetUsage(int $tenantId, int $userId, ?string $operation = null): void
    {
        if ($operation === null) {
            $operations = ['vision', 'chat', 'embedding'];
            foreach ($operations as $op) {
                $this->resetUsage($tenantId, $userId, $op);
            }
            return;
        }

        $this->cache->forget(self::USAGE_PREFIX . 'tenant:' . $tenantId . ':' . $operation);
        $this->cache->forget(self::USAGE_PREFIX . 'user:' . $userId . ':' . $operation);
        
        $this->logger->info('Usage reset', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'operation' => $operation,
        ]);
    }

    /**
     * Get usage statistics for billing
     */
    public function getUsageStats(int $tenantId, string $startDate, string $endDate): array
    {
        return $this->db->table('ai_usage_logs')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('operation, SUM(cost) as total_cost, COUNT(*) as total_requests')
            ->groupBy('operation')
            ->get()
            ->toArray();
    }

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly DatabaseManager $db,
        private readonly CarbonInterface $carbon,
        private readonly LoggerInterface $logger
    ) {}
}
