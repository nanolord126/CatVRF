<?php

namespace App\Domains\RealEstate\Enums;

enum PropertyType: string
{
    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case OFFICE = 'office';
    case COMMERCIAL = 'commercial';
    case LAND = 'land';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::APARTMENT => 'Квартира',
            self::HOUSE => 'Дом',
            self::OFFICE => 'Офис',
            self::COMMERCIAL => 'Коммерческое помещение',
            self::LAND => 'Земельный участок',
            self::OTHER => 'Прочее',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::APARTMENT => 'building',
            self::HOUSE => 'home',
            self::OFFICE => 'briefcase',
            self::COMMERCIAL => 'store',
            self::LAND => 'map',
            self::OTHER => 'question-circle',
        };
    }
}
