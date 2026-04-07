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

enum PropertyTypeEnum: string
{
    case Apartment  = 'apartment';
    case House      = 'house';
    case Land       = 'land';
    case Commercial = 'commercial';

    public function label(): string
    {
        return match ($this) {
            self::Apartment  => 'Квартира',
            self::House      => 'Дом',
            self::Land       => 'Земельный участок',
            self::Commercial => 'Коммерческая недвижимость',
        };
    }

    public function commissionPercent(): float
    {
        return match ($this) {
            self::Apartment  => 14.0,
            self::House      => 14.0,
            self::Land       => 14.0,
            self::Commercial => 14.0,
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
