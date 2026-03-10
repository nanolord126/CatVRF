<?php

namespace App\Domains\Geo\Enums;

enum LocationType: string
{
    case WAREHOUSE = 'warehouse';
    case SHOP = 'shop';
    case OFFICE = 'office';
    case SERVICE_CENTER = 'service_center';
    case BRANCH = 'branch';
    case PARTNER = 'partner';

    public function label(): string
    {
        return match($this) {
            self::WAREHOUSE => 'Склад',
            self::SHOP => 'Магазин',
            self::OFFICE => 'Офис',
            self::SERVICE_CENTER => 'Сервис-центр',
            self::BRANCH => 'Филиал',
            self::PARTNER => 'Партнер',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::WAREHOUSE => 'primary',
            self::SHOP => 'success',
            self::OFFICE => 'info',
            self::SERVICE_CENTER => 'warning',
            self::BRANCH => 'secondary',
            self::PARTNER => 'danger',
        };
    }
}
