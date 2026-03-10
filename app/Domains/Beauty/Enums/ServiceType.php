<?php

namespace App\Domains\Beauty\Enums;

enum ServiceType: string
{
    case HAIRCUT = 'haircut';
    case COLORING = 'coloring';
    case STYLING = 'styling';
    case MASSAGE = 'massage';
    case FACIAL = 'facial';
    case MANICURE = 'manicure';
    case PEDICURE = 'pedicure';
    case WAXING = 'waxing';

    public function label(): string
    {
        return match($this) {
            self::HAIRCUT => 'Стрижка',
            self::COLORING => 'Окрашивание',
            self::STYLING => 'Укладка',
            self::MASSAGE => 'Массаж',
            self::FACIAL => 'Уход за лицом',
            self::MANICURE => 'Маникюр',
            self::PEDICURE => 'Педикюр',
            self::WAXING => 'Депиляция',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::HAIRCUT => 'scissors',
            self::COLORING => 'palette',
            self::STYLING => 'wind',
            self::MASSAGE => 'relaxing',
            self::FACIAL => 'smile',
            self::MANICURE => 'hand',
            self::PEDICURE => 'foot',
            self::WAXING => 'sun',
        };
    }
}
