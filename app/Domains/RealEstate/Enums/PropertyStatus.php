<?php

namespace App\Domains\RealEstate\Enums;

enum PropertyStatus: string
{
    case AVAILABLE = 'available';
    case BOOKED = 'booked';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';
    case SOLD = 'sold';

    public function label(): string
    {
        return match($this) {
            self::AVAILABLE => 'Доступна',
            self::BOOKED => 'Забронирована',
            self::MAINTENANCE => 'На обслуживании',
            self::INACTIVE => 'Неактивна',
            self::SOLD => 'Продана',
        };
    }

    public function canBeBooked(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }
}
