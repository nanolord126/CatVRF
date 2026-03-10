<?php

namespace App\Domains\Education\Enums;

enum EnrollmentStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case DROPPED = 'dropped';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'На рассмотрении',
            self::ACTIVE => 'Активна',
            self::COMPLETED => 'Завершена',
            self::DROPPED => 'Прекращена',
            self::SUSPENDED => 'Приостановлена',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canAccessContent(): bool
    {
        return in_array($this, [self::ACTIVE, self::COMPLETED]);
    }
}
