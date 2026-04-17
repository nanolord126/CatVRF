<?php declare(strict_types=1);

namespace Modules\RealEstate\Enums;

enum PropertyStatus: string
{
    case AVAILABLE = 'available';
    case SOLD = 'sold';
    case RENTED = 'rented';
    case UNDER_CONTRACT = 'under_contract';
    case WITHDRAWN = 'withdrawn';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Доступно',
            self::SOLD => 'Продано',
            self::RENTED => 'Арендовано',
            self::UNDER_CONTRACT => 'В договоре',
            self::WITHDRAWN => 'Снято с продажи',
            self::ARCHIVED => 'В архиве',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function isSold(): bool
    {
        return $this === self::SOLD;
    }

    public function isRented(): bool
    {
        return $this === self::RENTED;
    }
}
