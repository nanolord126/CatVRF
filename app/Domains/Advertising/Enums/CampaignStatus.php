<?php

namespace App\Domains\Advertising\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::ACTIVE => 'Активна',
            self::PAUSED => 'На паузе',
            self::ENDED => 'Завершена',
            self::CANCELLED => 'Отменена',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }
}
