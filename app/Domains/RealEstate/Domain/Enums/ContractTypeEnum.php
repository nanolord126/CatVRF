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

enum ContractTypeEnum: string
{
    case Rental = 'rental';
    case Sale   = 'sale';

    public function label(): string
    {
        return match ($this) {
            self::Rental => 'Договор аренды',
            self::Sale   => 'Договор купли-продажи',
        };
    }

    public function commissionPercent(): float
    {
        return match ($this) {
            self::Rental => 14.0,
            self::Sale   => 14.0,
        };
    }

    /**
     * Returns the property status that should be applied after signing.
     */
    public function resultingPropertyStatus(): PropertyStatusEnum
    {
        return match ($this) {
            self::Rental => PropertyStatusEnum::Rented,
            self::Sale   => PropertyStatusEnum::Sold,
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(static fn (self $e) => $e->label(), self::cases()),
        );
    }
}
