<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активна',
            self::PAUSED => 'Приостановлена',
            self::CANCELLED => 'Отменена',
            self::EXPIRED => 'Истекла',
        };
    }

    public function canPause(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canResume(): bool
    {
        return $this === self::PAUSED;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAUSED], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::CANCELLED, self::EXPIRED], true);
    }
}
