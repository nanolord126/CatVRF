<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Enums;

enum MembershipStatus: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case SUSPENDED = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::FROZEN => 'Frozen',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function canBook(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canCheckIn(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canFreeze(): bool
    {
        return $this === self::ACTIVE;
    }
}
