<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Enums;

use App\Enums\BaseEnum;

/**
 * Deal Status — Статус сделки в CRM
 */
enum DealStatus: string implements BaseEnum
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::InProgress => 'В работе',
            self::Negotiation => 'На согласовании',
            self::Won => 'Выиграна',
            self::Lost => 'Проиграна',
            self::Cancelled => 'Отменена',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::InProgress => 'blue',
            self::Negotiation => 'yellow',
            self::Won => 'green',
            self::Lost => 'red',
            self::Cancelled => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Won, self::Lost, self::Cancelled]);
    }

    public function isPositive(): bool
    {
        return $this === self::Won;
    }

    public function isNegative(): bool
    {
        return in_array($this, [self::Lost, self::Cancelled]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::New, self::InProgress, self::Negotiation]);
    }
}
