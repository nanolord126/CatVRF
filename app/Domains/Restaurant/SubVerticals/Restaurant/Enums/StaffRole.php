<?php

declare(strict_types=1);

namespace Modules\Restaurant\Enums;

use Illuminate\Support\Str;

/**
 * Staff Role — Роль сотрудника в ресторане
 */
enum StaffRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case WAITER = 'waiter';
    case COOK = 'cook';
    case BARTENDER = 'bartender';
    case COURIER = 'courier';
    case CASHIER = 'cashier';
    case HOST = 'host';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Владелец',
            self::MANAGER => 'Управляющий',
            self::WAITER => 'Официант',
            self::COOK => 'Повар',
            self::BARTENDER => 'Бармен',
            self::COURIER => 'Курьер',
            self::CASHIER => 'Кассир',
            self::HOST => 'Хостес',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OWNER => 'Полный доступ ко всем функциям',
            self::MANAGER => 'Управление персоналом и отчётами',
            self::WAITER => 'Приём заказов и обслуживание гостей',
            self::COOK => 'Приготовление блюд',
            self::BARTENDER => 'Приготовление напитков',
            self::COURIER => 'Доставка заказов',
            self::CASHIER => 'Работа с кассой и оплатой',
            self::HOST => 'Встреча гостей и бронирование',
        };
    }

    public function canManageOrders(): bool
    {
        return in_array($this, [
            self::OWNER,
            self::MANAGER,
            self::WAITER,
            self::CASHIER,
        ], true);
    }

    public function canAccessKitchen(): bool
    {
        return in_array($this, [
            self::OWNER,
            self::MANAGER,
            self::COOK,
        ], true);
    }

    public function canAccessBar(): bool
    {
        return in_array($this, [
            self::OWNER,
            self::MANAGER,
            self::BARTENDER,
        ], true);
    }

    public function canDeliver(): bool
    {
        return in_array($this, [
            self::OWNER,
            self::MANAGER,
            self::COURIER,
        ], true);
    }

    public function canManageStaff(): bool
    {
        return in_array($this, [
            self::OWNER,
            self::MANAGER,
        ], true);
    }

    public function canViewAnalytics(): bool
    {
        return in_array($this, [
            self::OWNER,
            self::MANAGER,
        ], true);
    }

    public function canManageMenu(): bool
    {
        return in_array($this, [
            self::OWNER,
            self::MANAGER,
        ], true);
    }

    public function isFrontOfHouse(): bool
    {
        return in_array($this, [
            self::WAITER,
            self::BARTENDER,
            self::CASHIER,
            self::HOST,
        ], true);
    }

    public function isBackOfHouse(): bool
    {
        return in_array($this, [
            self::COOK,
            self::BARTENDER,
        ], true);
    }

    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if (Str::lower($case->label()) === Str::lower($label)) {
                return $case;
            }
        }

        return null;
    }
}
