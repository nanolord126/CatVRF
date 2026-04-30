<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PersonalDevGoalTracking Feature
 *
 * Controls the rollout of personal-dev-goal-tracking functionality.
 */
final class PersonalDevGoalTracking extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'personaldev';
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
