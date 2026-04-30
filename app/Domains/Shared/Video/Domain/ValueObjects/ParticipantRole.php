<?php

declare(strict_types=1);

namespace Modules\Video\Domain\ValueObjects;

enum ParticipantRole: string
{
    case HOST = 'host';
    case CO_HOST = 'co_host';
    case VIEWER = 'viewer';

    public function canControl(): bool
    {
        return in_array($this, [self::HOST, self::CO_HOST], true);
    }

    public function canBroadcast(): bool
    {
        return in_array($this, [self::HOST, self::CO_HOST], true);
    }

    public function canRecord(): bool
    {
        return $this === self::HOST;
    }

    public function canMute(): bool
    {
        return in_array($this, [self::HOST, self::CO_HOST], true);
    }
}
