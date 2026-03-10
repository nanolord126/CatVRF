<?php

namespace App\Domains\Taxi\Enums;

enum VehicleClass: string
{
    case ECONOMY = 'economy';
    case COMFORT = 'comfort';
    case COMFORT_PLUS = 'comfort_plus';
    case BUSINESS = 'business';
    case LUXURY = 'luxury';

    public function label(): string
    {
        return match($this) {
            self::ECONOMY => 'Эконом',
            self::COMFORT => 'Комфорт',
            self::COMFORT_PLUS => 'Комфорт+',
            self::BUSINESS => 'Бизнес',
            self::LUXURY => 'Премиум',
        };
    }

    public function priceMultiplier(): float
    {
        return match($this) {
            self::ECONOMY => 1.0,
            self::COMFORT => 1.5,
            self::COMFORT_PLUS => 2.0,
            self::BUSINESS => 2.5,
            self::LUXURY => 3.5,
        };
    }
}
