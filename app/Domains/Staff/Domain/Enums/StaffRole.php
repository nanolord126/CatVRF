<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Enums;

/**
 * StaffRole — роль сотрудника в пределах тенанта.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Каждая роль имеет русскоязычный лейбл, цвет Filament-badge,
 * икону для UI, уровень доступа и набор доступных разрешений.
 */
enum StaffRole: string
{
    case EMPLOYEE       = 'employee';        // Сотрудник / персонал
    case OBJECT_ADMIN   = 'object_admin';    // Администратор объекта
    case ACCOUNTANT     = 'accountant';      // Бухгалтер
    case ANALYST        = 'analyst';         // Аналитик
    case SMM_MANAGER    = 'smm_manager';     // SMM менеджер
    case MANAGER        = 'manager';         // Управющий
    case BUSINESS_OWNER = 'business_owner';  // Владелец бизнеса
    case INVESTOR       = 'investor';        // Инвестор

    /**
     * Возвращает локализованное название роли для отображения в UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE       => 'Сотрудник',
            self::OBJECT_ADMIN   => 'Администратор объекта',
            self::ACCOUNTANT     => 'Бухгалтер',
            self::ANALYST        => 'Аналитик',
            self::SMM_MANAGER    => 'SMM менеджер',
            self::MANAGER        => 'Управляющий',
            self::BUSINESS_OWNER => 'Владелец бизнеса',
            self::INVESTOR       => 'Инвестор',
        };
    }

    /**
     * Возвращает цвет Filament-badge для данной роли.
     */
    public function color(): string
    {
        return match ($this) {
            self::EMPLOYEE       => 'success',
            self::OBJECT_ADMIN   => 'info',
            self::ACCOUNTANT     => 'primary',
            self::ANALYST        => 'warning',
            self::SMM_MANAGER    => 'purple',
            self::MANAGER        => 'orange',
            self::BUSINESS_OWNER => 'danger',
            self::INVESTOR       => 'gray',
        };
    }

    /**
     * Возвращает имя иконки Heroicons для отображения в Filament.
     */
    public function icon(): string
    {
        return match ($this) {
            self::EMPLOYEE       => 'heroicon-o-user',
            self::OBJECT_ADMIN   => 'heroicon-o-building-office',
            self::ACCOUNTANT     => 'heroicon-o-calculator',
            self::ANALYST        => 'heroicon-o-chart-bar',
            self::SMM_MANAGER    => 'heroicon-o-device-phone-mobile',
            self::MANAGER        => 'heroicon-o-briefcase',
            self::BUSINESS_OWNER => 'heroicon-o-crown',
            self::INVESTOR       => 'heroicon-o-banknotes',
        };
    }

    /**
     * Возвращает уровень доступа роли (1-8, чем выше, тем больше прав).
     */
    public function accessLevel(): int
    {
        return match ($this) {
            self::EMPLOYEE       => 1,
            self::OBJECT_ADMIN   => 2,
            self::ACCOUNTANT     => 3,
            self::ANALYST        => 4,
            self::SMM_MANAGER    => 5,
            self::MANAGER        => 6,
            self::BUSINESS_OWNER => 7,
            self::INVESTOR       => 8,
        };
    }

    /**
     * Проверяет, имеет ли роль административные права.
     */
    public function hasAdminAccess(): bool
    {
        return $this->accessLevel() >= 6;
    }

    /**
     * Проверяет, может ли роль управлять другой ролью.
     */
    public function canManage(StaffRole $otherRole): bool
    {
        return $this->accessLevel() > $otherRole->accessLevel();
    }

    /**
     * Возвращает список разрешений для роли.
     */
    public function permissions(): array
    {
        return match ($this) {
            self::EMPLOYEE => [
                'staff.view_own',
                'staff.view_own_schedule',
                'staff.check_in',
                'staff.check_out',
                'staff.view_own_performance',
                'staff.view_own_wellness',
                'staff.view_own_learning',
                'staff.social.view',
                'staff.social.post',
                'staff.social.like',
                'staff.communication.send_messages',
            ],
            self::OBJECT_ADMIN => [
                'staff.view_own',
                'staff.view_own_schedule',
                'staff.check_in',
                'staff.check_out',
                'staff.view_own_performance',
                'staff.view_own_wellness',
                'staff.view_own_learning',
                'staff.social.view',
                'staff.social.post',
                'staff.social.like',
                'staff.communication.send_messages',
                'staff.view_object_staff',
                'staff.manage_object_schedule',
                'staff.approve_timeoff',
                'staff.view_object_analytics',
            ],
            self::ACCOUNTANT => [
                'staff.view_own',
                'staff.view_own_performance',
                'staff.finance.view_all',
                'staff.finance.view_salaries',
                'staff.finance.view_expenses',
                'staff.finance.view_revenue',
                'staff.finance.export_reports',
                'staff.finance.manage_payments',
                'staff.view_all',
                'staff.analytics.view_financial',
            ],
            self::ANALYST => [
                'staff.view_own',
                'staff.view_all',
                'staff.analytics.view_all',
                'staff.analytics.view_performance',
                'staff.analytics.view_wellness',
                'staff.analytics.view_learning',
                'staff.analytics.forecast',
                'staff.analytics.export_reports',
                'staff.analytics.view_team_dynamics',
                'staff.analytics.view_kpi',
            ],
            self::SMM_MANAGER => [
                'staff.view_own',
                'staff.view_all',
                'staff.social.manage_posts',
                'staff.social.manage_announcements',
                'staff.social.view_analytics',
                'staff.communication.manage_channels',
                'staff.communication.send_broadcasts',
                'staff.gamification.manage_challenges',
                'staff.gamification.view_leaderboard',
            ],
            self::MANAGER => [
                'staff.view_own',
                'staff.view_all',
                'staff.manage_all',
                'staff.create',
                'staff.update',
                'staff.delete',
                'staff.manage_schedule',
                'staff.approve_timeoff',
                'staff.approve_shift_swap',
                'staff.view_all_performance',
                'staff.manage_roles',
                'staff.analytics.view_all',
                'staff.communication.manage_all',
                'staff.gamification.manage_all',
                'staff.learning.manage_all',
                'staff.wellness.view_all',
            ],
            self::BUSINESS_OWNER => [
                'staff.view_own',
                'staff.view_all',
                'staff.manage_all',
                'staff.create',
                'staff.update',
                'staff.delete',
                'staff.manage_schedule',
                'staff.approve_timeoff',
                'staff.approve_shift_swap',
                'staff.view_all_performance',
                'staff.manage_roles',
                'staff.analytics.view_all',
                'staff.analytics.view_financial',
                'staff.finance.manage_all',
                'staff.communication.manage_all',
                'staff.gamification.manage_all',
                'staff.learning.manage_all',
                'staff.wellness.view_all',
                'staff.settings.manage',
                'staff.integrations.manage',
            ],
            self::INVESTOR => [
                'staff.view_all',
                'staff.analytics.view_all',
                'staff.analytics.view_financial',
                'staff.analytics.view_performance',
                'staff.analytics.forecast',
                'staff.analytics.export_reports',
                'staff.finance.view_revenue',
                'staff.finance.view_expenses',
                'staff.finance.view_roi',
            ],
        };
    }

    /**
     * Проверяет, имеет ли роль конкретное разрешение.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * Возвращает список доступных ролей для select-списков в Filament.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()], self::cases()),
            'label',
            'value',
        );
    }

    /**
     * Возвращает список ролей, отсортированный по уровню доступа.
     *
     * @return array<int, self>
     */
    public static function sortedByAccessLevel(): array
    {
        return collect(self::cases())->sortBy(fn (self $role) => $role->accessLevel())->values()->toArray();
    }
}
