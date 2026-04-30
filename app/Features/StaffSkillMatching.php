<?php

declare(strict_types=1);

namespace App\Features;

/**
 * StaffSkillMatching Feature
 *
 * Controls the rollout of staff-skill-matching functionality.
 */
final class StaffSkillMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'staff';
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
