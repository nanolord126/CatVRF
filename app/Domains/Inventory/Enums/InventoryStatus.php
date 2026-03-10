<?php

namespace App\Domains\Inventory\Enums;

enum InventoryStatus: string
{
    case IN_STOCK = 'in_stock';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case DISCONTINUED = 'discontinued';
    case ON_ORDER = 'on_order';

    public function label(): string
    {
        return match($this) {
            self::IN_STOCK => 'В наличии',
            self::LOW_STOCK => 'Низкий запас',
            self::OUT_OF_STOCK => 'Нет в наличии',
            self::DISCONTINUED => 'Снято с производства',
            self::ON_ORDER => 'На заказ',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::IN_STOCK => 'success',
            self::LOW_STOCK => 'warning',
            self::OUT_OF_STOCK => 'danger',
            self::DISCONTINUED => 'dark',
            self::ON_ORDER => 'info',
        };
    }

    public function canBePurchased(): bool
    {
        return in_array($this, [self::IN_STOCK, self::LOW_STOCK, self::ON_ORDER]);
    }
}
