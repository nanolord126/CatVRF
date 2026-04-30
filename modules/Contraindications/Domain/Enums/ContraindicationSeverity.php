<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Enums;

enum ContraindicationSeverity: string
{
    case Low = 'low';
    case Moderate = 'moderate';
    case High = 'high';
    case Critical = 'critical';
}
