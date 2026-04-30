<?php

declare(strict_types=1);

namespace Modules\Video\Domain\ValueObjects;

enum RoomStatus: string
{
    case SCHEDULED = 'scheduled';
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
    case RECORDED = 'recorded';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isEnded(): bool
    {
        return in_array($this, [self::ENDED, self::CANCELLED], true);
    }

    public function canJoin(): bool
    {
        return in_array($this, [self::SCHEDULED, self::ACTIVE], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::ENDED, self::CANCELLED, self::RECORDED], true);
    }
}
