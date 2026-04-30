<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Services\FraudControlService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Collection;

/**
 * StaffRoleService — сервис управления ролями и правами доступа.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Управляет назначением ролей, проверкой прав доступа,
 * иерархией ролей, разрешениями.
 */
final class StaffRoleService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Назначает роль сотруднику.
     */
    public function assignRole(Staff $staff, StaffRole $role, ?string $reason = null): Staff
    {
        $correlationId = (string) \Illuminate\Support\Str::uuid();
        $userId = auth()->id() ?? 0;

        $this->fraud->check(
            userId: $userId,
            operationType: 'staff_role_assign',
            amount: 0,
            correlationId: $correlationId
        );

        // Проверка: назначающий должен иметь более высокий уровень доступа
        $assignerRole = $this->getCurrentUserRole();
        if ($assignerRole && !$assignerRole->canManage($role)) {
            throw new \RuntimeException('Insufficient permissions to assign this role');
        }

        $oldRole = $staff->role;
        $staff->update(['role' => $role->value]);

        Cache::tags(['staff_roles', 'staff:' . $staff->id])->flush();

        $this->logAction(
            action: 'role_assigned',
            entityType: 'Staff',
            entityId: $staff->id,
            context: [
                'old_role' => $oldRole,
                'new_role' => $role->value,
                'role_label' => $role->label(),
                'reason' => $reason,
            ]
        );

        $this->logger->info('Staff role assigned', [
            'staff_id' => $staff->id,
            'new_role' => $role->value,
            'correlation_id' => $correlationId,
        ]);

        return $staff->fresh();
    }

    /**
     * Проверяет, имеет ли сотрудник конкретное разрешение.
     */
    public function hasPermission(Staff $staff, string $permission): bool
    {
        $role = StaffRole::tryFrom($staff->role);
        
        if (!$role) {
            return false;
        }

        return $role->hasPermission($permission);
    }

    /**
     * Проверяет, имеет ли сотрудник одно из разрешений.
     */
    public function hasAnyPermission(Staff $staff, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($staff, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, имеет ли сотрудник все указанные разрешения.
     */
    public function hasAllPermissions(Staff $staff, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($staff, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Возвращает все разрешения роли сотрудника.
     */
    public function getStaffPermissions(Staff $staff): array
    {
        $cacheKey = "staff_permissions:{$staff->id}";

        return Cache::tags(['staff_roles', 'staff:' . $staff->id])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($staff) {
                $role = StaffRole::tryFrom($staff->role);
                
                if (!$role) {
                    return [];
                }

                return $role->permissions();
            },
        );
    }

    /**
     * Возвращает роль текущего авторизованного пользователя.
     */
    public function getCurrentUserRole(): ?StaffRole
    {
        $staff = $this->getCurrentStaff();
        
        if (!$staff) {
            return null;
        }

        return StaffRole::tryFrom($staff->role);
    }

    /**
     * Возвращает текущего сотрудника (связанного с auth user).
     */
    public function getCurrentStaff(): ?Staff
    {
        $userId = Auth::id();
        
        if (!$userId) {
            return null;
        }

        return Staff::where('user_id', $userId)->first();
    }

    /**
     * Проверяет, может ли текущий пользователь управлять указанным сотрудником.
     */
    public function canManageStaff(Staff $targetStaff): bool
    {
        $currentRole = $this->getCurrentUserRole();
        
        if (!$currentRole) {
            return false;
        }

        // Владелец бизнеса и инвестор могут управлять всеми
        if (in_array($currentRole, [StaffRole::BUSINESS_OWNER, StaffRole::INVESTOR], true)) {
            return true;
        }

        // Управляющий может управлять всеми кроме владельца и инвестора
        if ($currentRole === StaffRole::MANAGER) {
            $targetRole = StaffRole::tryFrom($targetStaff->role);
            return $targetRole && $currentRole->canManage($targetRole);
        }

        // Другие роли могут управлять только сотрудниками с более низким уровнем
        $targetRole = StaffRole::tryFrom($targetStaff->role);
        return $targetRole && $currentRole->canManage($targetRole);
    }

    /**
     * Возвращает сотрудников по роли.
     */
    public function getStaffByRole(StaffRole $role, ?int $tenantId = null): Collection
    {
        $query = Staff::where('role', $role->value);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    /**
     * Возвращает статистику по ролям в тенанте.
     */
    public function getRoleStats(?int $tenantId = null): array
    {
        $tenantKey = $tenantId ?? 'all';
        $cacheKey = "role_stats:{$tenantKey}";

        return Cache::tags(['staff_roles'])->remember(
            $cacheKey,
            now()->addHours(12),
            function () use ($tenantId) {
                $query = Staff::query();

                if ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                }

                $stats = $query->get()
                    ->groupBy('role')
                    ->map(fn ($group) => [
                        'count' => $group->count(),
                        'role_label' => StaffRole::tryFrom($group->first()->role)?->label() ?? 'Unknown',
                        'access_level' => StaffRole::tryFrom($group->first()->role)?->accessLevel() ?? 0,
                    ])
                    ->toArray();

                // Добавляем роли с 0 сотрудников
                foreach (StaffRole::cases() as $role) {
                    if (!isset($stats[$role->value])) {
                        $stats[$role->value] = [
                            'count' => 0,
                            'role_label' => $role->label(),
                            'access_level' => $role->accessLevel(),
                        ];
                    }
                }

                return $stats;
            },
        );
    }

    /**
     * Проверяет доступ к ресурсу на основе прав.
     */
    public function authorize(Staff $staff, string $permission): bool
    {
        if (!$this->hasPermission($staff, $permission)) {
            $this->logger->warning('Access denied', [
                'staff_id' => $staff->id,
                'permission' => $permission,
                'role' => $staff->role,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Возвращает матрицу разрешений для всех ролей.
     */
    public function getPermissionsMatrix(): array
    {
        $matrix = [];

        foreach (StaffRole::sortedByAccessLevel() as $role) {
            $matrix[$role->value] = [
                'label' => $role->label(),
                'access_level' => $role->accessLevel(),
                'permissions' => $role->permissions(),
            ];
        }

        return $matrix;
    }
}
