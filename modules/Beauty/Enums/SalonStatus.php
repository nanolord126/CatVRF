<?php

namespace App\Domains\Beauty\Enums;

enum SalonStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case TEMPORARILY_CLOSED = 'temporarily_closed';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Открыто',
            self::CLOSED => 'Закрыто',
            self::TEMPORARILY_CLOSED => 'Временно закрыто',
            self::MAINTENANCE => 'На обслуживании',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::OPEN;
    }
}
