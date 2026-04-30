<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Services\StaffRoleService;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * StaffPolicy — политика авторизации для сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Определяет права доступа к действиям над сотрудниками
 * на основе ролей и разрешений RBAC.
 */
final class StaffPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly StaffRoleService $roleService
    ) {}

    /**
     * Определяет, может ли пользователь просматривать список сотрудников.
     */
    public function viewAny(?Staff $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->roleService->hasPermission($user, 'staff.view_all');
    }

    /**
     * Определяет, может ли пользователь просматривать конкретного сотрудника.
     */
    public function view(Staff $user, Staff $staff): bool
    {
        // Сотрудник может просматривать свои данные
        if ($user->id === $staff->id) {
            return $this->roleService->hasPermission($user, 'staff.view_own');
        }

        // Для просмотра других сотрудников нужно соответствующее разрешение
        return $this->roleService->hasPermission($user, 'staff.view_all');
    }

    /**
     * Определяет, может ли пользователь создавать сотрудников.
     */
    public function create(Staff $user): bool
    {
        return $this->roleService->hasPermission($user, 'staff.create');
    }

    /**
     * Определяет, может ли пользователь обновлять данные сотрудника.
     */
    public function update(Staff $user, Staff $staff): bool
    {
        // Сотрудник может обновлять свои данные
        if ($user->id === $staff->id) {
            return $this->roleService->hasPermission($user, 'staff.view_own');
        }

        // Для обновления других сотрудников нужно разрешение
        if (!$this->roleService->hasPermission($user, 'staff.update')) {
            return false;
        }

        // Проверка иерархии ролей
        return $this->roleService->canManageStaff($staff);
    }

    /**
     * Определяет, может ли пользователь удалять сотрудника.
     */
    public function delete(Staff $user, Staff $staff): bool
    {
        if (!$this->roleService->hasPermission($user, 'staff.delete')) {
            return false;
        }

        return $this->roleService->canManageStaff($staff);
    }

    /**
     * Определяет, может ли пользователь управлять расписанием.
     */
    public function manageSchedule(Staff $user, Staff $staff): bool
    {
        if (!$this->roleService->hasPermission($user, 'staff.manage_schedule')) {
            return false;
        }

        // Администратор объекта может управлять расписанием в своем объекте
        $userRole = StaffRole::tryFrom($user->role);
        if ($userRole === StaffRole::OBJECT_ADMIN) {
            return $user->tenant_id === $staff->tenant_id;
        }

        // Управляющий и выше могут управлять любым расписанием
        return $userRole?->hasAdminAccess() ?? false;
    }

    /**
     * Определяет, может ли пользователь одобрять отпуска.
     */
    public function approveTimeoff(Staff $user): bool
    {
        return $this->roleService->hasPermission($user, 'staff.approve_timeoff');
    }

    /**
     * Определяет, может ли пользователь одобрять подмены смен.
     */
    public function approveShiftSwap(Staff $user): bool
    {
        return $this->roleService->hasPermission($user, 'staff.approve_shift_swap');
    }

    /**
     * Определяет, может ли пользователь управлять ролями.
     */
    public function manageRoles(Staff $user): bool
    {
        return $this->roleService->hasPermission($user, 'staff.manage_roles');
    }

    /**
     * Определяет, может ли пользователь назначать конкретную роль.
     */
    public function assignRole(Staff $user, StaffRole $targetRole): bool
    {
        $userRole = StaffRole::tryFrom($user->role);
        
        if (!$userRole) {
            return false;
        }

        return $userRole->canManage($targetRole);
    }

    /**
     * Определяет, может ли пользователь просматривать производительность.
     */
    public function viewPerformance(Staff $user, Staff $staff): bool
    {
        // Сотрудник может просматривать свою производительность
        if ($user->id === $staff->id) {
            return $this->roleService->hasPermission($user, 'staff.view_own_performance');
        }

        // Для просмотра чужой производительности нужно разрешение
        return $this->roleService->hasPermission($user, 'staff.view_all_performance');
    }

    /**
     * Определяет, может ли пользователь просматривать аналитику.
     */
    public function viewAnalytics(Staff $user, string $type = 'all'): bool
    {
        return match ($type) {
            'financial' => $this->roleService->hasPermission($user, 'staff.analytics.view_financial'),
            'performance' => $this->roleService->hasPermission($user, 'staff.analytics.view_performance'),
            'wellness' => $this->roleService->hasPermission($user, 'staff.analytics.view_wellness'),
            'learning' => $this->roleService->hasPermission($user, 'staff.analytics.view_learning'),
            'all' => $this->roleService->hasPermission($user, 'staff.analytics.view_all'),
            default => false,
        };
    }

    /**
     * Определяет, может ли пользователь просматривать финансовые данные.
     */
    public function viewFinance(Staff $user, string $type = 'all'): bool
    {
        return match ($type) {
            'salaries' => $this->roleService->hasPermission($user, 'staff.finance.view_salaries'),
            'expenses' => $this->roleService->hasPermission($user, 'staff.finance.view_expenses'),
            'revenue' => $this->roleService->hasPermission($user, 'staff.finance.view_revenue'),
            'all' => $this->roleService->hasPermission($user, 'staff.finance.view_all'),
            default => false,
        };
    }

    /**
     * Определяет, может ли пользователь управлять социальными функциями.
     */
    public function manageSocial(Staff $user, string $action = 'view'): bool
    {
        return match ($action) {
            'manage_posts' => $this->roleService->hasPermission($user, 'staff.social.manage_posts'),
            'manage_announcements' => $this->roleService->hasPermission($user, 'staff.social.manage_announcements'),
            'view_analytics' => $this->roleService->hasPermission($user, 'staff.social.view_analytics'),
            default => true, // Все сотрудники могут просматривать социальные функции
        };
    }

    /**
     * Определяет, может ли пользователь управлять коммуникацией.
     */
    public function manageCommunication(Staff $user, string $action = 'send'): bool
    {
        return match ($action) {
            'manage_channels' => $this->roleService->hasPermission($user, 'staff.communication.manage_channels'),
            'send_broadcasts' => $this->roleService->hasPermission($user, 'staff.communication.send_broadcasts'),
            'manage_all' => $this->roleService->hasPermission($user, 'staff.communication.manage_all'),
            default => $this->roleService->hasPermission($user, 'staff.communication.send_messages'),
        };
    }

    /**
     * Определяет, может ли пользователь управлять геймификацией.
     */
    public function manageGamification(Staff $user, string $action = 'view'): bool
    {
        return match ($action) {
            'manage_challenges' => $this->roleService->hasPermission($user, 'staff.gamification.manage_challenges'),
            'manage_all' => $this->roleService->hasPermission($user, 'staff.gamification.manage_all'),
            'view_leaderboard' => $this->roleService->hasPermission($user, 'staff.gamification.view_leaderboard'),
            default => true,
        };
    }

    /**
     * Определяет, может ли пользователь управлять обучением.
     */
    public function manageLearning(Staff $user): bool
    {
        return $this->roleService->hasPermission($user, 'staff.learning.manage_all');
    }

    /**
     * Определяет, может ли пользователь просматривать данные о благополучии.
     */
    public function viewWellness(Staff $user, Staff $staff): bool
    {
        // Сотрудник может просматривать свои данные о благополучии
        if ($user->id === $staff->id) {
            return $this->roleService->hasPermission($user, 'staff.view_own_wellness');
        }

        // Управляющий и выше могут просматривать все данные
        $userRole = StaffRole::tryFrom($user->role);
        return $userRole?->hasAdminAccess() ?? false;
    }

    /**
     * Определяет, может ли пользователь управлять настройками.
     */
    public function manageSettings(Staff $user): bool
    {
        return $this->roleService->hasPermission($user, 'staff.settings.manage');
    }

    /**
     * Определяет, может ли пользователь управлять интеграциями.
     */
    public function manageIntegrations(Staff $user): bool
    {
        return $this->roleService->hasPermission($user, 'staff.integrations.manage');
    }
}
