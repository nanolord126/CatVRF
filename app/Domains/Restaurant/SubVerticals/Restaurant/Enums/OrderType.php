<?php

declare(strict_types=1);

namespace Modules\Restaurant\Enums;

use Illuminate\Support\Str;

/**
 * Order Type — Тип заказа в ресторане
 */
enum OrderType: string
{
    case DINE_IN = 'dine_in';
    case DELIVERY = 'delivery';
    case PICKUP = 'pickup';
    case PRE_ORDER = 'pre_order';

    public function label(): string
    {
        return match ($this) {
            self::DINE_IN => 'В зале',
            self::DELIVERY => 'Доставка',
            self::PICKUP => 'Самовывоз',
            self::PRE_ORDER => 'Предзаказ',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DINE_IN => 'Заказ на столик в зале ресторана',
            self::DELIVERY => 'Доставка курьером по адресу',
            self::PICKUP => 'Самовывоз из ресторана',
            self::PRE_ORDER => 'Предзаказ на будущее время',
        };
    }

    public function requiresTable(): bool
    {
        return $this === self::DINE_IN;
    }

    public function requiresDelivery(): bool
    {
        return $this === self::DELIVERY;
    }

    public function requiresAddress(): bool
    {
        return $this === self::DELIVERY;
    }

    public function requiresPickupTime(): bool
    {
        return $this === self::PICKUP || $this === self::PRE_ORDER;
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
