<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';
    case WAITLIST = 'waitlist';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::CHECKED_IN => 'Checked In',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
            self::WAITLIST => 'Waitlist',
        };
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    public function canCheckIn(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::NO_SHOW], true);
    }
}
