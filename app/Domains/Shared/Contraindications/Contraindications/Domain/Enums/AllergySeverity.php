<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Enums;

enum AllergySeverity: string
{
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';
    case LifeThreatening = 'life_threatening';
}
