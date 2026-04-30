<?php

declare(strict_types=1);

namespace App\Features;

/**
 * EducationAITutor Feature
 *
 * Controls the rollout of education-a-i-tutor functionality.
 */
final class EducationAITutor extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'education';
    }

    protected function getBetaTenants(): array
    {
        return [];
    }

    protected function canActivate(): bool
    {
        return true;
    }
}
