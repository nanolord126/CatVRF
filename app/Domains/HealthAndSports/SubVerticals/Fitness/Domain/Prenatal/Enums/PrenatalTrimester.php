<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Prenatal\Enums;

enum PrenatalTrimester: string
{
    case FIRST = 'first';
    case SECOND = 'second';
    case THIRD = 'third';
}
