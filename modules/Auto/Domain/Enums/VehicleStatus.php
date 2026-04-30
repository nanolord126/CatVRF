<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Enums;

enum VehicleStatus: string
{
    case AVAILABLE = 'available';
    case IN_USE = 'in_use';
    case MAINTENANCE = 'maintenance';
    case OUT_OF_SERVICE = 'out_of_service';
    case RETIRED = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::IN_USE => 'In Use',
            self::MAINTENANCE => 'Maintenance',
            self::OUT_OF_SERVICE => 'Out of Service',
            self::RETIRED => 'Retired',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function canBeReserved(): bool
    {
        return in_array($this, [self::AVAILABLE, self::MAINTENANCE], true);
    }
}
