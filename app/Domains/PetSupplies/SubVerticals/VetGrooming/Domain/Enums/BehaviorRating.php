<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum BehaviorRating: string
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';
    case AGGRESSIVE = 'aggressive';

    public function getLabel(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Отлично',
            self::GOOD => 'Хорошо',
            self::FAIR => 'Удовлетворительно',
            self::POOR => 'Плохо',
            self::AGGRESSIVE => 'Агрессивно',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EXCELLENT => 'green',
            self::GOOD => 'blue',
            self::FAIR => 'yellow',
            self::POOR => 'orange',
            self::AGGRESSIVE => 'red',
        };
    }

    public function requiresExtraCaution(): bool
    {
        return in_array($this, [self::POOR, self::AGGRESSIVE]);
    }
}
