<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FreelanceSkillAssessment Feature
 *
 * Controls the rollout of freelance-skill-assessment functionality.
 */
final class FreelanceSkillAssessment extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'freelance';
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
