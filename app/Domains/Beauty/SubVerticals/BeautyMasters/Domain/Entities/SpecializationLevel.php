<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

enum SpecializationLevel: string
{
    case BASIC = 'basic';
    case ADVANCED = 'advanced';
    case EXPERT = 'expert';

    public function getLabel(): string
    {
        return match ($this) {
            self::BASIC => 'Basic',
            self::ADVANCED => 'Advanced',
            self::EXPERT => 'Expert',
        };
    }
}
