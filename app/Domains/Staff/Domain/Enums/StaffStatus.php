<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Enums;

/**
 * StaffStatus — статус сотрудника.
 *
 * Используется в Eloquent-моделях, Filament-ресурсах,
 * фильтрации таблиц и бизнес-правилах.
 */
enum StaffStatus: string
{
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';
    case ON_LEAVE = 'on_leave';
    case FIRED    = 'fired';

    /**
     * Русскоязычный лейбл статуса.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE   => 'Активен',
            self::INACTIVE => 'Неактивен',
            self::ON_LEAVE => 'В отпуске',
            self::FIRED    => 'Уволен',
        };
    }

    /**
     * Цвет Filament-badge для данного статуса.
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE   => 'success',
            self::INACTIVE => 'gray',
            self::ON_LEAVE => 'warning',
            self::FIRED    => 'danger',
        };
    }

    /**
     * Иконка Heroicons для Filament.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE   => 'heroicon-o-check-circle',
            self::INACTIVE => 'heroicon-o-x-circle',
            self::ON_LEAVE => 'heroicon-o-pause-circle',
            self::FIRED    => 'heroicon-o-no-symbol',
        };
    }

    /**
     * Проверяет, активен ли сотрудник.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Проверяет, доступен ли сотрудник для назначения на задачу.
     */
    public function isAvailableForAssignment(): bool
    {
        return in_array($this, [self::ACTIVE], strict: true);
    }

    /**
     * Список статусов для Filament-select.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $s) => ['value' => $s->value, 'label' => $s->label()], self::cases()),
            'label',
            'value',
        );
    }
}
