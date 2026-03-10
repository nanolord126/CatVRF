<?php

namespace App\Domains\Insurance\Enums;

enum PolicyStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case LAPSED = 'lapsed';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'На рассмотрении',
            self::ACTIVE => 'Активна',
            self::EXPIRED => 'Истекла',
            self::CANCELLED => 'Отменена',
            self::LAPSED => 'Прекращена',
            self::SUSPENDED => 'Приостановлена',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canBeClaimed(): bool
    {
        return $this === self::ACTIVE;
    }
}
