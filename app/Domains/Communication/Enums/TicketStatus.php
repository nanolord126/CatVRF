<?php

namespace App\Domains\Communication\Enums;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Открыта',
            self::IN_PROGRESS => 'В работе',
            self::ON_HOLD => 'Ожидание',
            self::RESOLVED => 'Разрешена',
            self::CLOSED => 'Закрыта',
            self::REOPENED => 'Переоткрыта',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::OPEN => 'danger',
            self::IN_PROGRESS => 'info',
            self::ON_HOLD => 'warning',
            self::RESOLVED => 'success',
            self::CLOSED => 'secondary',
            self::REOPENED => 'danger',
        };
    }

    public function canBeClosed(): bool
    {
        return in_array($this, [self::RESOLVED, self::CLOSED]);
    }
}
