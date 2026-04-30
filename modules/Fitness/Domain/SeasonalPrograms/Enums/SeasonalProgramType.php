<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Enums;

enum SeasonalProgramType: string
{
    case GROUP = 'group';
    case INDIVIDUAL = 'individual';
    case HYBRID = 'hybrid';

    public function getLabel(): string
    {
        return match ($this) {
            self::GROUP => 'Групповая',
            self::INDIVIDUAL => 'Индивидуальная',
            self::HYBRID => 'Гибридная',
        };
    }
}
