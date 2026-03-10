<?php

namespace App\Domains\Events\Enums;

enum EventStatus: string
{
    case DRAFT = 'draft';
    case UPCOMING = 'upcoming';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case POSTPONED = 'postponed';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::UPCOMING => 'Предстоящее',
            self::ONGOING => 'Происходит',
            self::COMPLETED => 'Завершено',
            self::CANCELLED => 'Отменено',
            self::POSTPONED => 'Отложено',
        };
    }

    public function canTicketsBeSold(): bool
    {
        return in_array($this, [self::DRAFT, self::UPCOMING]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::UPCOMING, self::ONGOING]);
    }
}
