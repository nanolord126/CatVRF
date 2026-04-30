<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConstructionScheduleOptimization Feature
 *
 * Controls the rollout of construction-schedule-optimization functionality.
 */
final class ConstructionScheduleOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'construction';
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
