<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\ValueObjects;

enum Scope: string
{
    case Cosmetology = 'cosmetology';
    case Food = 'food';
    case Medical = 'medical';
    case Grooming = 'grooming';
    case Fitness = 'fitness';
}
