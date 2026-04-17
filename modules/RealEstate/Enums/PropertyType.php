<?php declare(strict_types=1);

namespace Modules\RealEstate\Enums;

enum PropertyType: string
{
    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case COMMERCIAL = 'commercial';
    case LAND = 'land';
    case PARKING = 'parking';
    case WAREHOUSE = 'warehouse';

    public function label(): string
    {
        return match ($this) {
            self::APARTMENT => 'Квартира',
            self::HOUSE => 'Дом',
            self::COMMERCIAL => 'Коммерческая недвижимость',
            self::LAND => 'Земельный участок',
            self::PARKING => 'Парковочное место',
            self::WAREHOUSE => 'Склад',
        };
    }

    public function isResidential(): bool
    {
        return in_array($this, [self::APARTMENT, self::HOUSE], true);
    }

    public function isCommercial(): bool
    {
        return in_array($this, [self::COMMERCIAL, self::WAREHOUSE, self::PARKING], true);
    }
}
