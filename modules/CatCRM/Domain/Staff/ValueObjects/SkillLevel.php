<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * SkillLevel — Enum уровня навыка
 */
enum SkillLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Expert = 'expert';
    case Master = 'master';
}
