<?php

declare(strict_types=1);

namespace Modules\Hotels\Enums;

/**
 * Booking Status — Статус бронирования в гостинице
 * CatCRM Standard Color Scheme 2026
 */
enum BookingStatus: string
{
    case PENDING = 'pending';
    case PREPAID = 'prepaid';
    case PAID = 'paid';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::PREPAID => 'Предоплата внесена',
            self::PAID => 'Полностью оплачен',
            self::CONFIRMED => 'Подтверждён',
            self::CHECKED_IN => 'Гость заехал',
            self::CHECKED_OUT => 'Гость выехал',
            self::CANCELLED => 'Отменён',
            self::NO_SHOW => 'Неявка',
        };
    }

    public function filamentColor(): string
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
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::PREPAID => 'heroicon-o-banknotes',
            self::PAID => 'heroicon-o-check-circle',
            self::CONFIRMED => 'heroicon-o-shield-check',
            self::CHECKED_IN => 'heroicon-o-arrow-down-on-square',
            self::CHECKED_OUT => 'heroicon-o-arrow-up-on-square',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::NO_SHOW => 'heroicon-o-user-minus',
        };
    }

    public function color(): string
    {
        return $this->filamentColor();
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::NO_SHOW,
            self::CHECKED_OUT,
        ], true);
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::CONFIRMED,
            self::CHECKED_IN,
        ], true);
    }

    public function isPaid(): bool
    {
        return in_array($this, [
            self::PREPAID,
            self::PAID,
        ], true);
    }

    public function canTransitionTo(BookingStatus $status): bool
    {
        $transitions = [
            self::PENDING => [self::PREPAID, self::PAID, self::CONFIRMED, self::CANCELLED, self::NO_SHOW],
            self::PREPAID => [self::PAID, self::CONFIRMED, self::CANCELLED, self::NO_SHOW],
            self::PAID => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::CHECKED_IN, self::CANCELLED, self::NO_SHOW],
            self::CHECKED_IN => [self::CHECKED_OUT],
            self::CHECKED_OUT => [],
            self::CANCELLED => [],
            self::NO_SHOW => [],
        ];

        return in_array($status, $transitions[$this] ?? [], true);
    }
}
