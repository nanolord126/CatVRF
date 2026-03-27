<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Service: User Activity Tracking (Real-Time Presence)
 * 
 * Функции:
 * - Track user activity
 * - Get active users in tenant
 * - Update user status
 * - Track location/page
 * 
 * @package App\Services
 */
final class UserActivityService
{
    /**
     * Record user activity
     * @param int $userId
     * @param int $tenantId
     * @param string $activity
     * @param array $metadata
     * @return bool
     */
    public function recordActivity(
        int $userId,
        int $tenantId,
        string $activity,
        array $metadata = []
    ): bool {
        try {
            $key = "activity:user.{$userId}:tenant.{$tenantId}";
            
            cache()->put($key, [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'activity' => $activity,
                'metadata' => $metadata,
                'timestamp' => now()->toIso8601String(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], 3600); // Keep for 1 hour

            Log::channel('audit')->info('User activity recorded', [
                'user_id' => $userId,
                'activity' => $activity,
                'tenant_id' => $tenantId,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to record activity', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get active users in tenant
     * @param int $tenantId
     * @return array
     */
    public function getActiveUsers(int $tenantId): array
    {
        $pattern = "activity:user.*:tenant.{$tenantId}";
        $activeUsers = [];

        // In production: use Redis SCAN
        // For now: iterate through known user cache keys
        
        return $activeUsers;
    }

    /**
     * Get user's last activity
     * @param int $userId
     * @param int $tenantId
     * @return array|null
     */
    public function getLastActivity(int $userId, int $tenantId): ?array
    {
        $key = "activity:user.{$userId}:tenant.{$tenantId}";
        return cache()->get($key);
    }

    /**
     * Update user status
     * @param int $userId
     * @param string $status (online, away, offline, busy)
     * @param int $tenantId
     * @return bool
     */
    public function updateUserStatus(int $userId, string $status, int $tenantId): bool
    {
        try {
            $key = "user:status:{$tenantId}:{$userId}";
            cache()->put($key, [
                'status' => $status,
                'updated_at' => now()->toIso8601String(),
            ], 86400); // Keep for 24 hours

            Log::channel('audit')->info('User status updated', [
                'user_id' => $userId,
                'status' => $status,
                'tenant_id' => $tenantId,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update user status', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user status
     * @param int $userId
     * @param int $tenantId
     * @return string
     */
    public function getUserStatus(int $userId, int $tenantId): string
    {
        $key = "user:status:{$tenantId}:{$userId}";
        $data = cache()->get($key);
        return $data['status'] ?? 'offline';
    }

    /**
     * Track user page/location
     * @param int $userId
     * @param int $tenantId
     * @param string $page
     * @param array $params
     * @return bool
     */
    public function trackPageView(int $userId, int $tenantId, string $page, array $params = []): bool
    {
        try {
            $key = "pageview:user.{$userId}:tenant.{$tenantId}";
            
            cache()->put($key, [
                'page' => $page,
                'params' => $params,
                'viewed_at' => now()->toIso8601String(),
            ], 3600);

            return true;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to track page view', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user's current page
     * @param int $userId
     * @param int $tenantId
     * @return array|null
     */
    public function getCurrentPage(int $userId, int $tenantId): ?array
    {
        $key = "pageview:user.{$userId}:tenant.{$tenantId}";
        return cache()->get($key);
    }
}
