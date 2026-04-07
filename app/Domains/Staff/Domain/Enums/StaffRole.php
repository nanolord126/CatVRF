<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Enums;

/**
 * StaffRole — роль сотрудника в пределах тенанта.
 *
 * Каждая роль имеет русскоязычный лейбл, цвет Filament-badge,
 * икону для UI и набор доступных разрешений.
 */
enum StaffRole: string
{
    case ADMIN      = 'admin';
    case MANAGER    = 'manager';
    case EMPLOYEE   = 'employee';
    case ACCOUNTANT = 'accountant';

    /**
     * Возвращает локализованное название роли для отображения в UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN      => 'Администратор',
            self::MANAGER    => 'Менеджер',
            self::EMPLOYEE   => 'Сотрудник',
            self::ACCOUNTANT => 'Бухгалтер',
        };
    }

    /**
     * Возвращает цвет Filament-badge для данной роли.
     */
    public function color(): string
    {
        return match ($this) {
            self::ADMIN      => 'danger',
            self::MANAGER    => 'warning',
            self::EMPLOYEE   => 'success',
            self::ACCOUNTANT => 'info',
        };
    }

    /**
     * Возвращает имя иконки Heroicons для отображения в Filament.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ADMIN      => 'heroicon-o-shield-check',
            self::MANAGER    => 'heroicon-o-briefcase',
            self::EMPLOYEE   => 'heroicon-o-user',
            self::ACCOUNTANT => 'heroicon-o-calculator',
        };
    }

    /**
     * Проверяет, имеет ли роль административные права (администратор или менеджер).
     */
    public function hasAdminAccess(): bool
    {
        return in_array($this, [self::ADMIN, self::MANAGER], strict: true);
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
}
