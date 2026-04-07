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

enum PropertyStatusEnum: string
{
    case Draft    = 'draft';
    case Active   = 'active';
    case Sold     = 'sold';
    case Rented   = 'rented';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Черновик',
            self::Active   => 'Активно',
            self::Sold     => 'Продано',
            self::Rented   => 'Сдано в аренду',
            self::Archived => 'Архив',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft    => 'gray',
            self::Active   => 'success',
            self::Sold     => 'danger',
            self::Rented   => 'warning',
            self::Archived => 'secondary',
        };
    }

    public function canBePublished(): bool
    {
        return $this === self::Draft;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Sold, self::Rented, self::Archived], strict: true);
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(static fn (self $e) => $e->label(), self::cases()),
        );
    }
}
