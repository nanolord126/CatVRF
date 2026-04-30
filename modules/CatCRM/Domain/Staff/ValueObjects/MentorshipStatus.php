<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * MentorshipStatus — Enum статуса наставничества
 */
enum MentorshipStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Paused = 'paused';
}
