<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum KitchenStationType: string
{
    case COLD = 'cold';
    case HOT = 'hot';
    case BAR = 'bar';
    case DESSERT = 'dessert';
    case EXPEDITION = 'expedition';
    case GRILL = 'grill';
    case PIZZA = 'pizza';
    case SUSHI = 'sushi';

    public function label(): string
    {
        return match ($this) {
            self::COLD => 'Холодный цех',
            self::HOT => 'Горячий цех',
            self::BAR => 'Бар',
            self::DESSERT => 'Десерты',
            self::EXPEDITION => 'Экспедиция',
            self::GRILL => 'Гриль',
            self::PIZZA => 'Пицца',
            self::SUSHI => 'Суши',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COLD => 'blue',
            self::HOT => 'red',
            self::BAR => 'purple',
            self::DESSERT => 'pink',
            self::EXPEDITION => 'green',
            self::GRILL => 'orange',
            self::PIZZA => 'yellow',
            self::SUSHI => 'cyan',
        };
    }
}
