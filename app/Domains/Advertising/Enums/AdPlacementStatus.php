<?php

namespace App\Domains\Advertising\Enums;

enum AdPlacementStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case ENDED = 'ended';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'На модерации',
            self::APPROVED => 'Одобрено',
            self::REJECTED => 'Отклонено',
            self::ACTIVE => 'Активно',
            self::PAUSED => 'На паузе',
            self::ENDED => 'Завершено',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::ACTIVE => 'primary',
            self::PAUSED => 'secondary',
            self::ENDED => 'dark',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canBeEdited(): bool
    {
        return in_array($this, [self::PENDING, self::PAUSED]);
    }
}
