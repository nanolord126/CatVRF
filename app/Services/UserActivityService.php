<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Carbon\CarbonImmutable;

final readonly class UserActivityService
{
    use WithAuditLogging;

    private const ACTIVITY_TTL_SECONDS = 3600;

    private const STATUS_TTL_SECONDS = 86400;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly LogManager $log,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Record user activity
     */
    public function recordActivity(
        int $userId,
        int $tenantId,
        string $activity,
        array $metadata = []
    ): bool {
        try {
            $correlationId = Str::uuid()->toString();
            $key = "activity:user.{$userId}:tenant.{$tenantId}";
            $indexKey = "activity:index:tenant.{$tenantId}";

            $this->cache->put($key, [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'activity' => $activity,
                'metadata' => $metadata,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'correlation_id' => $correlationId,
            ], self::ACTIVITY_TTL_SECONDS);

            $indexedUsers = $this->cache->get($indexKey, []);
            $indexedUsers[] = $userId;
            $this->cache->put($indexKey, array_values(array_unique($indexedUsers)), self::ACTIVITY_TTL_SECONDS);

            $this->logger->channel('audit')->info('User activity recorded', [
                'user_id' => $userId,
                'activity' => $activity,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to record activity', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get active users in tenant
     */
    public function getActiveUsers(int $tenantId): array
    {
        $activeUsers = [];
        $indexKey = "activity:index:tenant.{$tenantId}";
        $indexedUsers = $this->cache->get($indexKey, []);

        foreach ($indexedUsers as $userId) {
            $key = "activity:user.{$userId}:tenant.{$tenantId}";
            $activity = $this->cache->get($key);

            if (is_array($activity)) {
                $activeUsers[] = $activity;
            }
        }

        usort($activeUsers, static fn (array $a, array $b): int => strcmp(
            (string) ($b['timestamp'] ?? ''),
            (string) ($a['timestamp'] ?? ''),
        ));

        return $activeUsers;
    }

    /**
     * Get user's last activity
     */
    public function getLastActivity(int $userId, int $tenantId): ?array
    {
        $key = "activity:user.{$userId}:tenant.{$tenantId}";
        $value = $this->cache->get($key);

        return is_array($value) ? $value : null;
    }

    /**
     * Update user status
     *
     * @param  string  $status  (online, away, offline, busy)
     */
    public function updateUserStatus(int $userId, string $status, int $tenantId): bool
    {
        try {
            $correlationId = Str::uuid()->toString();
            $key = "user:status:{$tenantId}:{$userId}";
            $this->cache->put($key, [
                'status' => $status,
                'updated_at' => CarbonImmutable::now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ], self::STATUS_TTL_SECONDS);

            $this->logger->channel('audit')->info('User status updated', [
                'user_id' => $userId,
                'status' => $status,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to update user status', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user status
     */
    public function getUserStatus(int $userId, int $tenantId): string
    {
        $key = "user:status:{$tenantId}:{$userId}";
        $data = $this->cache->get($key);

        return $data['status'] ?? 'offline';
    }

    /**
     * Track user page/location
     */
    public function trackPageView(int $userId, int $tenantId, string $page, array $params = []): bool
    {
        try {
            $key = "pageview:user.{$userId}:tenant.{$tenantId}";

            $this->cache->put($key, [
                'page' => $page,
                'params' => $params,
                'viewed_at' => CarbonImmutable::now()->toIso8601String(),
                'correlation_id' => Str::uuid()->toString(),
            ], self::ACTIVITY_TTL_SECONDS);

            return true;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to track page view', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user's current page
     */
    public function getCurrentPage(int $userId, int $tenantId): ?array
    {
        $key = "pageview:user.{$userId}:tenant.{$tenantId}";
        $value = $this->cache->get($key);

        return is_array($value) ? $value : null;
    }
}
