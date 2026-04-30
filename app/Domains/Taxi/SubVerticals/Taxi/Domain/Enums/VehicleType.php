<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Enums;

enum VehicleType: string
{
    case ECONOMY = 'economy';
    case COMFORT = 'comfort';
    case PREMIUM = 'premium';
    case VAN = 'van';
    case ELECTRIC = 'electric';

    public function label(): string
    {
        return match ($this) {
            self::ECONOMY => 'Economy',
            self::COMFORT => 'Comfort',
            self::PREMIUM => 'Premium',
            self::VAN => 'Van',
            self::ELECTRIC => 'Electric',
        };
    }

    public function priceMultiplier(): float
    {
        return match ($this) {
            self::ECONOMY => 1.0,
            self::COMFORT => 1.3,
            self::PREMIUM => 1.8,
            self::VAN => 1.5,
            self::ELECTRIC => 1.2,
        };
    }
}
