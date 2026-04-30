<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Enums;

/**
 * Enum PropertyType
 *
 *
 * @comment Типы недвижимости для вертикали RealEstate.
 * - APARTMENT: Квартира
 * - HOUSE: Дом, коттедж, таунхаус
 * - LAND_PLOT: Земельный участок
 * - COMMERCIAL: Коммерческая недвижимость (офис, склад, ритейл)
 * - READY_BUSINESS: Готовый бизнес на продажу
 */
enum PropertyType: string
{
    /**
     * Получить человекочитаемое название типа.
     */
    public function label(): string
    {
        return match ($this) {
            self::APARTMENT => 'Квартира',
            self::HOUSE => 'Дом / Коттедж',
            self::LAND_PLOT => 'Земельный участок',
            self::COMMERCIAL => 'Коммерческая недвижимость',
            self::READY_BUSINESS => 'Готовый бизнес',
        };
    }

    /**
     * Получить все значения в виде массива.
     *
     * @return array<string, string>
     */
    public static function asArray(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }

    /**
     * Проверяет, является ли тип жилой недвижимостью.
     */
    public function isResidential(): bool
    {
        return in_array($this, [self::APARTMENT, self::HOUSE], true);
    }

    /**
     * Проверяет, является ли тип коммерческой недвижимостью.
     */
    public function isCommercial(): bool
    {
        return in_array($this, [self::COMMERCIAL, self::READY_BUSINESS], true);
    }
    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case LAND_PLOT = 'land_plot';
    case COMMERCIAL = 'commercial';
    case READY_BUSINESS = 'ready_business';
}
