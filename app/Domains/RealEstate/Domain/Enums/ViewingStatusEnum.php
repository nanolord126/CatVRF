<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\RealEstate\Domain\Enums;

enum ViewingStatusEnum: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Ожидает подтверждения',
            self::Confirmed => 'Подтверждён',
            self::Completed => 'Проведён',
            self::Cancelled => 'Отменён',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'warning',
            self::Confirmed => 'success',
            self::Completed => 'info',
            self::Cancelled => 'danger',
        };
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::Pending;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], strict: true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], strict: true);
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(static fn (self $e) => $e->label(), self::cases()),
        );
    }
}
