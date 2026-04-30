<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Enums;

enum PropertyStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case SOLD = 'sold';
    case UNDER_CONTRACT = 'under_contract';
    case OFF_MARKET = 'off_market';
    case PENDING_APPROVAL = 'pending_approval';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::RESERVED => 'Reserved',
            self::SOLD => 'Sold',
            self::UNDER_CONTRACT => 'Under Contract',
            self::OFF_MARKET => 'Off Market',
            self::PENDING_APPROVAL => 'Pending Approval',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function canBeReserved(): bool
    {
        return in_array($this, [self::AVAILABLE, self::OFF_MARKET], true);
    }

    public function isSold(): bool
    {
        return $this === self::SOLD;
    }
}
