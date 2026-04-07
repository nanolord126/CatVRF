<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Enums;

/**
 * Vertical — вертикаль бизнеса, к которой прикреплён сотрудник.
 *
 * Содержит информацию о комиссии платформы и бонусе за миграцию.
 * Используется в профиле сотрудника для разделения бизнес-логики по вертикалям.
 */
enum Vertical: string
{
    case BEAUTY      = 'beauty';
    case TAXI        = 'taxi';
    case DELIVERY    = 'delivery';
    case FOOD        = 'food';
    case REAL_ESTATE = 'real_estate';
    case HOTELS      = 'hotels';
    case AUTO        = 'auto';
    case MEDICAL     = 'medical';
    case PET         = 'pet';
    case EDUCATION   = 'education';

    /**
     * Человекочитаемое название вертикали на русском.
     */
    public function label(): string
    {
        return match ($this) {
            self::BEAUTY      => 'Красота и здоровье',
            self::TAXI        => 'Такси',
            self::DELIVERY    => 'Доставка',
            self::FOOD        => 'Еда и рестораны',
            self::REAL_ESTATE => 'Недвижимость',
            self::HOTELS      => 'Отели и гостиницы',
            self::AUTO        => 'Автомобили',
            self::MEDICAL     => 'Медицина',
            self::PET         => 'Животные',
            self::EDUCATION   => 'Образование',
        };
    }

    /**
     * Комиссия платформы в процентах для данной вертикали (стандарт 14%).
     */
    public function platformCommission(): float
    {
        return match ($this) {
            self::TAXI    => 15.0,
            self::HOTELS  => 14.0,
            self::BEAUTY, self::FOOD, self::DELIVERY,
            self::REAL_ESTATE, self::AUTO,
            self::MEDICAL, self::PET, self::EDUCATION => 14.0,
        };
    }

    /**
     * Пониженная комиссия при миграции с другой платформы (10% первые 4 месяца).
     */
    public function migrationCommission(): float
    {
        return match ($this) {
            self::BEAUTY   => 10.0,
            self::HOTELS   => 12.0,
            default        => 12.0,
        };
    }

    /**
     * Иконка Heroicons для Filament-навигации.
     */
    public function icon(): string
    {
        return match ($this) {
            self::BEAUTY      => 'heroicon-o-sparkles',
            self::TAXI        => 'heroicon-o-truck',
            self::DELIVERY    => 'heroicon-o-archive-box-arrow-down',
            self::FOOD        => 'heroicon-o-cake',
            self::REAL_ESTATE => 'heroicon-o-home',
            self::HOTELS      => 'heroicon-o-building-office',
            self::AUTO        => 'heroicon-o-wrench-screwdriver',
            self::MEDICAL     => 'heroicon-o-heart',
            self::PET         => 'heroicon-o-star',
            self::EDUCATION   => 'heroicon-o-academic-cap',
        };
    }

    /**
     * Опции для Filament select.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $v) => ['value' => $v->value, 'label' => $v->label()], self::cases()),
            'label',
            'value',
        );
    }
}
