<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Enums;

enum WorkoutIntensity: string
{
    case VERY_LOW = 'very_low';
    case LOW = 'low';
    case MODERATE = 'moderate';
    case HIGH = 'high';
    case VERY_HIGH = 'very_high';

    public function getLabel(): string
    {
        return match ($this) {
            self::VERY_LOW => 'Very Low',
            self::LOW => 'Low',
            self::MODERATE => 'Moderate',
            self::HIGH => 'High',
            self::VERY_HIGH => 'Very High',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::VERY_LOW => 'green',
            self::LOW => 'lime',
            self::MODERATE => 'yellow',
            self::HIGH => 'orange',
            self::VERY_HIGH => 'red',
        };
    }

    public function getCalorieBurnPerHour(): int
    {
        return match ($this) {
            self::VERY_LOW => 200,
            self::LOW => 300,
            self::MODERATE => 450,
            self::HIGH => 600,
            self::VERY_HIGH => 800,
        };
    }
}
