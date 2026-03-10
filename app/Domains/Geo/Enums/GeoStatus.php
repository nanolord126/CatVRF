<?php

namespace App\Domains\Geo\Enums;

enum GeoStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case UNDER_MAINTENANCE = 'under_maintenance';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Активна',
            self::INACTIVE => 'Неактивна',
            self::UNDER_MAINTENANCE => 'На обслуживании',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
