<?php

declare(strict_types=1);

namespace Modules\Video\Domain\ValueObjects;

enum ParticipantStatus: string
{
    case INVITED = 'invited';
    case JOINED = 'joined';
    case LEFT = 'left';
    case REJECTED = 'rejected';

    public function isInRoom(): bool
    {
        return $this === self::JOINED;
    }

    public function hasLeft(): bool
    {
        return $this === self::LEFT;
    }
}
