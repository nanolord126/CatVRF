<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Senior\Enums;

enum SeniorFitnessLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';
}
