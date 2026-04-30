<?php

declare(strict_types=1);

namespace App\Services\Protection;

use App\Enums\Role;
use App\Exceptions\RoleLimitExceededException;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

/**
 * Role Isolation Service
 *
 * Enforces role limits per user and per tenant to prevent privilege escalation.
 * - Max 5 roles per user (in a single tenant)
 * - Max 3 Owner roles per tenant
 * - Max 10 Staff roles per tenant
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class RoleIsolationService
{
    private const CACHE_TTL_MINUTES = 30;

    public function __construct(
        private readonly CacheManager $cache,
        private readonly ConfigRepository $config,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
    ) {}

    /**
     * Check if role can be assigned to user in tenant.
     *
     * @param  User  $user  User to assign role to
     * @param  Tenant  $tenant  Tenant context
     * @param  Role  $role  Role to assign
     * @return array{allowed: bool, reason?: string, current_count?: int, limit?: int}
     */
    public function canAssignRole(User $user, Tenant $tenant, Role $role): array
    {
        // Check user-level limit
        $userLimitCheck = $this->checkUserRoleLimit($user, $tenant);
        if (! $userLimitCheck['allowed']) {
            return $userLimitCheck;
        }

        // Check tenant-level limit for specific role type
        $tenantLimitCheck = $this->checkTenantRoleLimit($tenant, $role);
        if (! $tenantLimitCheck['allowed']) {
            return $tenantLimitCheck;
        }

        return ['allowed' => true];
    }

    /**
     * Assign role with limit enforcement.
     *
     * @param  User  $user  User to assign role to
     * @param  Tenant  $tenant  Tenant context
     * @param  Role  $role  Role to assign
     * @param  int|null  $assignedBy  User ID who is assigning the role
     * @return bool
     *
     * @throws RoleLimitExceededException
     */
    public function assignRole(User $user, Tenant $tenant, Role $role, ?int $assignedBy = null): bool
    {
        // Check limits first
        $check = $this->canAssignRole($user, $tenant, $role);
        if (! $check['allowed']) {
            throw new RoleLimitExceededException(
                $check['reason'] ?? 'Role limit exceeded',
                $check['current_count'] ?? 0,
                $check['limit'] ?? 0,
            );
        }

        return $this->db->transaction(function () use ($user, $tenant, $role, $assignedBy) {
            // Attach user to tenant with role
            $user->tenants()->syncWithoutDetaching([
                $tenant->id => [
                    'role' => $role->value,
                    'is_active' => true,
                    'accepted_at' => now(),
                ],
            ]);

            // Invalidate cache
            $this->invalidateUserCache($user->id, $tenant->id);
            $this->invalidateTenantCache($tenant->id);

            // Log assignment
            $this->log->channel('audit')->$this->logger->info('Role assigned with limit enforcement', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'role' => $role->value,
                'assigned_by' => $assignedBy,
            ]);

            return true;
        });
    }

    /**
     * Remove role from user in tenant.
     *
     * @param  User  $user  User to remove role from
     * @param  Tenant  $tenant  Tenant context
     * @param  int|null  $removedBy  User ID who is removing the role
     * @return bool
     */
    public function removeRole(User $user, Tenant $tenant, ?int $removedBy = null): bool
    {
        return $this->db->transaction(function () use ($user, $tenant, $removedBy) {
            $user->tenants()->detach($tenant->id);

            // Invalidate cache
            $this->invalidateUserCache($user->id, $tenant->id);
            $this->invalidateTenantCache($tenant->id);

            // Log removal
            $this->log->channel('audit')->$this->logger->info('Role removed', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'removed_by' => $removedBy,
            ]);

            return true;
        });
    }

    /**
     * Get current role count for user in tenant.
     *
     * @param  User  $user  User to check
     * @param  Tenant  $tenant  Tenant context
     * @return int
     */
    public function getUserRoleCount(User $user, Tenant $tenant): int
    {
        $cacheKey = $this->getUserCacheKey($user->id, $tenant->id);

        return $this->cache->remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($user, $tenant) {
            return $user->tenants()
                ->where('tenant_id', $tenant->id)
                ->where('tenant_user.is_active', true)
                ->count();
        });
    }

    /**
     * Get current role count for tenant by role type.
     *
     * @param  Tenant  $tenant  Tenant to check
     * @param  Role  $role  Role type to count
     * @return int
     */
    public function getTenantRoleCount(Tenant $tenant, Role $role): int
    {
        $cacheKey = $this->getTenantRoleCacheKey($tenant->id, $role);

        return $this->cache->remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($tenant, $role) {
            return $tenant->activeUsers()
                ->wherePivot('role', $role->value)
                ->count();
        });
    }

    /**
     * Get total staff count for tenant (all roles except Owner).
     *
     * @param  Tenant  $tenant  Tenant to check
     * @return int
     */
    public function getTenantStaffCount(Tenant $tenant): int
    {
        $cacheKey = $this->getTenantStaffCacheKey($tenant->id);

        return $this->cache->remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($tenant) {
            return $tenant->activeUsers()
                ->wherePivot('role', '!=', Role::Owner->value)
                ->count();
        });
    }

    /**
     * Check user-level role limit.
     *
     * @param  User  $user  User to check
     * @param  Tenant  $tenant  Tenant context
     * @return array{allowed: bool, reason?: string, current_count?: int, limit?: int}
     */
    private function checkUserRoleLimit(User $user, Tenant $tenant): array
    {
        $maxRoles = $this->config->get('protection.max_roles_per_user', 5);
        $currentCount = $this->getUserRoleCount($user, $tenant);

        if ($currentCount >= $maxRoles) {
            return [
                'allowed' => false,
                'reason' => "Пользователь уже имеет максимум {$maxRoles} ролей в этом Tenant. Сначала удалите существующую роль.",
                'current_count' => $currentCount,
                'limit' => $maxRoles,
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Check tenant-level role limit.
     *
     * @param  Tenant  $tenant  Tenant to check
     * @param  Role  $role  Role to assign
     * @return array{allowed: bool, reason?: string, current_count?: int, limit?: int}
     */
    private function checkTenantRoleLimit(Tenant $tenant, Role $role): array
    {
        // Owner limit
        if ($role === Role::Owner) {
            $maxOwners = $this->config->get('protection.max_owners_per_tenant', 3);
            $currentOwners = $this->getTenantRoleCount($tenant, Role::Owner);

            if ($currentOwners >= $maxOwners) {
                return [
                    'allowed' => false,
                    'reason' => "Tenant уже имеет максимум {$maxOwners} владельцев. Сначала удалите существующего владельца.",
                    'current_count' => $currentOwners,
                    'limit' => $maxOwners,
                ];
            }
        }

        // Staff limit (all non-owner roles)
        if ($role !== Role::Owner) {
            $maxStaff = $this->config->get('protection.max_staff_per_tenant', 10);
            $currentStaff = $this->getTenantStaffCount($tenant);

            if ($currentStaff >= $maxStaff) {
                return [
                    'allowed' => false,
                    'reason' => "Tenant уже имеет максимум {$maxStaff} сотрудников. Сначала удалите существующего сотрудника.",
                    'current_count' => $currentStaff,
                    'limit' => $maxStaff,
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * Get cache key for user role count.
     *
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return string
     */
    private function getUserCacheKey(int $userId, int $tenantId): string
    {
        return "role_count:user:{$userId}:tenant:{$tenantId}";
    }

    /**
     * Get cache key for tenant role count.
     *
     * @param  int  $tenantId  Tenant ID
     * @param  Role  $role  Role
     * @return string
     */
    private function getTenantRoleCacheKey(int $tenantId, Role $role): string
    {
        return "role_count:tenant:{$tenantId}:role:{$role->value}";
    }

    /**
     * Get cache key for tenant staff count.
     *
     * @param  int  $tenantId  Tenant ID
     * @return string
     */
    private function getTenantStaffCacheKey(int $tenantId): string
    {
        return "role_count:tenant:{$tenantId}:staff";
    }

    /**
     * Invalidate user role cache.
     *
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return void
     */
    private function invalidateUserCache(int $userId, int $tenantId): void
    {
        $this->cache->forget($this->getUserCacheKey($userId, $tenantId));
    }

    /**
     * Invalidate tenant role cache.
     *
     * @param  int  $tenantId  Tenant ID
     * @return void
     */
    private function invalidateTenantCache(int $tenantId): void
    {
        foreach (Role::businessRoles() as $role) {
            $this->cache->forget($this->getTenantRoleCacheKey($tenantId, $role));
        }
        $this->cache->forget($this->getTenantStaffCacheKey($tenantId));
    }
}
