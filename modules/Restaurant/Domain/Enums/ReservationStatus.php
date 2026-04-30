<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case ARRIVED = 'arrived';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждено',
            self::ARRIVED => 'Гости прибыли',
            self::COMPLETED => 'Завершено',
            self::CANCELLED => 'Отменено',
            self::NO_SHOW => 'Не пришли',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::CONFIRMED => 'blue',
            self::ARRIVED => 'green',
            self::COMPLETED => 'emerald',
            self::CANCELLED => 'red',
            self::NO_SHOW => 'orange',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::NO_SHOW], true);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED, self::ARRIVED], true);
    }
}
