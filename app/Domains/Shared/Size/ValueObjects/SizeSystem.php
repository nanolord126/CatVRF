<?php

declare(strict_types=1);

namespace App\Domains\Shared\Size\ValueObjects;

enum SizeSystem: string
{
    case NUMERIC = 'numeric';      // S, M, L, XL
    case LETTER = 'letter';        // S, M, L, XL
    case EU_NUMERIC = 'eu_numeric'; // 36, 38, 40, 42
    case US_NUMERIC = 'us_numeric'; // 4, 6, 8, 10
    case UK_NUMERIC = 'uk_numeric'; // 6, 8, 10, 12
    case RU_NUMERIC = 'ru_numeric'; // 40, 42, 44, 46
    case CM = 'cm';                 // Centimeters (foot length)
    case INCH = 'inch';             // Inches (foot length)

    public function getLabel(): string
    {
        return match ($this) {
            self::NUMERIC => 'Цифровой',
            self::LETTER => 'Буквенный',
            self::EU_NUMERIC => 'Европейский',
            self::US_NUMERIC => 'Американский',
            self::UK_NUMERIC => 'Британский',
            self::RU_NUMERIC => 'Российский',
            self::CM => 'Сантиметры',
            self::INCH => 'Дюймы',
        };
    }

    public function getCategory(): string
    {
        return match ($this) {
            self::NUMERIC, self::LETTER, self::EU_NUMERIC, self::US_NUMERIC, self::UK_NUMERIC, self::RU_NUMERIC => 'fashion',
            self::CM, self::INCH => 'footwear',
        };
    }

    public function isFashion(): bool
    {
        return in_array($this, [
            self::NUMERIC,
            self::LETTER,
            self::EU_NUMERIC,
            self::US_NUMERIC,
            self::UK_NUMERIC,
            self::RU_NUMERIC,
        ]);
    }

    public function isFootwear(): bool
    {
        return in_array($this, [self::CM, self::INCH]);
    }
}
