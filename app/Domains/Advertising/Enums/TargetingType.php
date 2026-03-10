<?php

namespace App\Domains\Advertising\Enums;

enum TargetingType: string
{
    case DEMOGRAPHIC = 'demographic';       // Возраст, пол, доход
    case GEOGRAPHIC = 'geographic';         // География
    case INTEREST = 'interest';             // Интересы
    case BEHAVIORAL = 'behavioral';         // Поведение
    case CUSTOM_AUDIENCE = 'custom_audience';
    case LOOKALIKE = 'lookalike';          // Похожая аудитория

    public function label(): string
    {
        return match($this) {
            self::DEMOGRAPHIC => 'Демографические данные',
            self::GEOGRAPHIC => 'География',
            self::INTEREST => 'Интересы',
            self::BEHAVIORAL => 'Поведение',
            self::CUSTOM_AUDIENCE => 'Пользовательская аудитория',
            self::LOOKALIKE => 'Похожая аудитория',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::DEMOGRAPHIC => 'users',
            self::GEOGRAPHIC => 'map',
            self::INTEREST => 'heart',
            self::BEHAVIORAL => 'activity',
            self::CUSTOM_AUDIENCE => 'folder',
            self::LOOKALIKE => 'copy',
        };
    }
}
