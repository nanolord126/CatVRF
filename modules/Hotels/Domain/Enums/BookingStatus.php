<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case PREPAID = 'prepaid';
    case PAID = 'paid';
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::PREPAID => 'Предоплата',
            self::PAID => 'Оплачено',
            self::CHECKED_IN => 'Заезд выполнен',
            self::CHECKED_OUT => 'Выезд выполнен',
            self::CANCELLED => 'Отменён',
            self::NO_SHOW => 'Не появился',
            self::COMPLETED => 'Завершён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#f97316', // warning (yellow)
            self::PREPAID => '#0ea5e9', // sky (light blue)
            self::PAID => '#22c55e', // success (green)
            self::CONFIRMED => '#3b82f6', // info (blue)
            self::CHECKED_IN => '#8b5cf6', // violet (purple)
            self::CHECKED_OUT => '#166534', // success (dark green)
            self::CANCELLED => '#ef4444', // danger (red)
            self::NO_SHOW => '#ef4444', // danger (red)
            self::COMPLETED => '#06b6d4', // cyan
        };
    }

    public function getFilamentColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PREPAID => 'sky',
            self::PAID => 'success',
            self::CONFIRMED => 'info',
            self::CHECKED_IN => 'violet',
            self::CHECKED_OUT => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'danger',
            self::COMPLETED => 'cyan',
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::PREPAID, self::PAID]);
    }

    public function canBeCheckedIn(): bool
    {
        return in_array($this, [self::PREPAID, self::PAID]);
    }

    public function canBeCheckedOut(): bool
    {
        return $this === self::CHECKED_IN;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::CANCELLED, self::NO_SHOW, self::COMPLETED]);
    }
}
