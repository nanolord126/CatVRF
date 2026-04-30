<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\CooldownActionType;
use App\Enums\CooldownStatus;
use App\Events\Security\CooldownStarted;
use App\Models\CooldownPeriod;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * Cooldown Service
 * 
 * Manages cooling-off periods for high-risk actions.
 * Provides methods to start, check, and manage cooldowns.
 * 
 * Production 2026 CANON:
 * - Redis-based caching for fast checks
 * - Race condition prevention with locks
 * - Audit logging for all cooldown operations
 * - Multi-tenancy support with priority to tenant-level cooldowns
 */
final class CooldownService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes
    private const LOCK_TTL_SECONDS = 10; // 10 seconds
    private const REDIS_KEY_PREFIX = 'cooldown:';
    private const REDIS_LOCK_PREFIX = 'cooldown_lock:';

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly AuditService $auditService,
        private readonly CacheManager $cache,
        private readonly LogManager $log
    ) {}

    /**
     * Start a cooldown period for a specific action
     * 
     * @param  User  $user  The user triggering the cooldown
     * @param  CooldownActionType  $actionType  The action type
     * @param  int  $hours  Duration in hours (uses default if not specified)
     * @param  string|null  $reason  Optional reason for the cooldown
     * @param  int|null  $tenantId  Optional tenant ID (uses current tenant if not specified)
     * @param  array  $metadata  Additional metadata
     * @return CooldownPeriod
     */
    public function startCooldown(
        User $user,
        CooldownActionType $actionType,
        ?int $hours = null,
        ?string $reason = null,
        ?int $tenantId = null,
        array $metadata = []
    ): CooldownPeriod {
        $tenantId = $tenantId ?? $user->tenant_id;
        $hours = $hours ?? $actionType->getDefaultDurationHours();
        $reason = $reason ?? "Triggered by {$actionType->getLabel()}";

        // Use Redis lock to prevent race conditions
        $lockKey = self::REDIS_LOCK_PREFIX . "{$user->id}:{$actionType->value}";
        $lock = $this->cache->lock($lockKey, self::LOCK_TTL_SECONDS);

        try {
            $lock->block(5);

            // Check if active cooldown already exists
            $existingCooldown = $this->getActiveCooldown($user->id, $tenantId, $actionType);
            if ($existingCooldown !== null) {
                $this->log->info('Cooldown already active, returning existing', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                    'action_type' => $actionType->value,
                    'existing_expires_at' => $existingCooldown->expires_at,
                ]);

                return $existingCooldown;
            }

            // Create cooldown record
            $cooldown = CooldownPeriod::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'action_type' => $actionType->value,
                'triggered_at' => CarbonImmutable::now(),
                'expires_at' => CarbonImmutable::now()->addHours($hours),
                'reason' => $reason,
                'status' => CooldownStatus::ACTIVE->value,
                'metadata' => array_merge($metadata, [
                    'duration_hours' => $hours,
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]),
            ]);

            // Cache the cooldown for fast checks
            $this->cacheCooldown($cooldown);

            // Dispatch event for notifications
            $this->eventDispatcher->dispatch(new CooldownStarted($cooldown, $user));

            // Log to audit
            $this->auditService->logEvent('cooldown_started', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'action_type' => $actionType->value,
                'duration_hours' => $hours,
                'expires_at' => $cooldown->expires_at->toIso8601String(),
                'reason' => $reason,
            ], 'security');

            $this->log->info('Cooldown period started', [
                'cooldown_id' => $cooldown->id,
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'action_type' => $actionType->value,
                'expires_at' => $cooldown->expires_at->toIso8601String(),
            ]);

            return $cooldown;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Check if user/tenant is under cooldown for a specific action
     * 
     * @param  User  $user  The user to check
     * @param  CooldownActionType  $actionType  The action type to check
     * @param  int|null  $tenantId  Optional tenant ID
     * @return bool True if under cooldown
     */
    public function isUnderCooldown(
        User $user,
        CooldownActionType $actionType,
        ?int $tenantId = null
    ): bool {
        $tenantId = $tenantId ?? $user->tenant_id;

        // Check cache first for performance
        $cacheKey = $this->getCacheKey($user->id, $tenantId, $actionType);
        $cachedResult = $this->cache->get($cacheKey);

        if ($cachedResult !== null) {
            return (bool) $cachedResult;
        }

        // Check database
        $cooldown = $this->getActiveCooldown($user->id, $tenantId, $actionType);
        $isUnderCooldown = $cooldown !== null;

        // Cache the result
        $this->cache->put($cacheKey, $isUnderCooldown ? 1 : 0, self::CACHE_TTL_SECONDS);

        return $isUnderCooldown;
    }

    /**
     * Get remaining cooldown time in seconds
     * 
     * @param  User  $user  The user to check
     * @param  CooldownActionType  $actionType  The action type to check
     * @param  int|null  $tenantId  Optional tenant ID
     * @return int Remaining seconds (0 if not under cooldown)
     */
    public function getRemainingTime(
        User $user,
        CooldownActionType $actionType,
        ?int $tenantId = null
    ): int {
        $tenantId = $tenantId ?? $user->tenant_id;

        $cooldown = $this->getActiveCooldown($user->id, $tenantId, $actionType);

        if ($cooldown === null) {
            return 0;
        }

        return $cooldown->getRemainingSeconds();
    }

    /**
     * Get remaining cooldown time in human-readable format
     * 
     * @param  User  $user  The user to check
     * @param  CooldownActionType  $actionType  The action type to check
     * @param  int|null  $tenantId  Optional tenant ID
     * @return string Human-readable time or empty string if not under cooldown
     */
    public function getRemainingTimeForHumans(
        User $user,
        CooldownActionType $actionType,
        ?int $tenantId = null
    ): string {
        $seconds = $this->getRemainingTime($user, $actionType, $tenantId);

        if ($seconds === 0) {
            return '';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours} ч. {$minutes} мин.";
        }

        return "{$minutes} мин.";
    }

    /**
     * Get active cooldown for user/tenant and action type
     * 
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @param  CooldownActionType  $actionType  Action type
     * @return CooldownPeriod|null
     */
    public function getActiveCooldown(
        int $userId,
        int $tenantId,
        CooldownActionType $actionType
    ): ?CooldownPeriod {
        // Check tenant-level cooldown first (higher priority)
        $tenantCooldown = CooldownPeriod::active()
            ->forTenant($tenantId)
            ->byActionType($actionType)
            ->first();

        if ($tenantCooldown !== null) {
            return $tenantCooldown;
        }

        // Check user-level cooldown
        $userCooldown = CooldownPeriod::active()
            ->forUser($userId)
            ->byActionType($actionType)
            ->first();

        return $userCooldown;
    }

    /**
     * Override a cooldown period manually
     * 
     * @param  CooldownPeriod  $cooldown  The cooldown to override
     * @param  int  $overriddenBy  User ID of the admin overriding
     * @param  string  $reason  Reason for override
     * @return bool
     */
    public function overrideCooldown(
        CooldownPeriod $cooldown,
        int $overriddenBy,
        string $reason
    ): bool {
        $result = $cooldown->override($overriddenBy, $reason);

        if ($result) {
            // Clear cache
            $cacheKey = $this->getCacheKey(
                $cooldown->user_id,
                $cooldown->tenant_id,
                $cooldown->getActionTypeEnum()
            );
            $this->cache->forget($cacheKey);

            // Log to audit
            $this->auditService->logEvent('cooldown_overridden', [
                'cooldown_id' => $cooldown->id,
                'user_id' => $cooldown->user_id,
                'tenant_id' => $cooldown->tenant_id,
                'action_type' => $cooldown->action_type,
                'overridden_by' => $overriddenBy,
                'reason' => $reason,
            ], 'security');

            $this->log->info('Cooldown period overridden', [
                'cooldown_id' => $cooldown->id,
                'overridden_by' => $overriddenBy,
                'reason' => $reason,
            ]);
        }

        return $result;
    }

    /**
     * Get all active cooldowns for a user
     * 
     * @param  User  $user  The user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveCooldownsForUser(User $user)
    {
        return CooldownPeriod::active()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('tenant_id', $user->tenant_id);
            })
            ->get();
    }

    /**
     * Get all active cooldowns for a tenant
     * 
     * @param  int  $tenantId  Tenant ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveCooldownsForTenant(int $tenantId)
    {
        return CooldownPeriod::active()
            ->forTenant($tenantId)
            ->get();
    }

    /**
     * Mark expired cooldowns as expired
     * Should be called by a scheduled job
     * 
     * @return int Number of cooldowns marked as expired
     */
    public function markExpiredCooldowns(): int
    {
        $expiredCooldowns = CooldownPeriod::expired()->get();
        $count = 0;

        foreach ($expiredCooldowns as $cooldown) {
            $cooldown->markAsExpired();
            
            // Clear cache
            $cacheKey = $this->getCacheKey(
                $cooldown->user_id,
                $cooldown->tenant_id,
                $cooldown->getActionTypeEnum()
            );
            $this->cache->forget($cacheKey);
            
            $count++;
        }

        if ($count > 0) {
            $this->log->info('Marked expired cooldowns', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Clear cooldown cache for a specific user/tenant/action
     * 
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @param  CooldownActionType  $actionType  Action type
     * @return void
     */
    public function clearCache(
        int $userId,
        int $tenantId,
        CooldownActionType $actionType
    ): void {
        $cacheKey = $this->getCacheKey($userId, $tenantId, $actionType);
        $this->cache->forget($cacheKey);
    }

    /**
     * Generate cache key for cooldown check
     */
    private function getCacheKey(
        int $userId,
        int $tenantId,
        CooldownActionType $actionType
    ): string {
        return self::REDIS_KEY_PREFIX . "{$userId}:{$tenantId}:{$actionType->value}";
    }

    /**
     * Cache cooldown for fast checks
     */
    private function cacheCooldown(CooldownPeriod $cooldown): void
    {
        $cacheKey = $this->getCacheKey(
            $cooldown->user_id,
            $cooldown->tenant_id,
            $cooldown->getActionTypeEnum()
        );
        
        $ttl = $cooldown->getRemainingSeconds();
        $this->cache->put($cacheKey, 1, min($ttl, self::CACHE_TTL_SECONDS));
    }
}
