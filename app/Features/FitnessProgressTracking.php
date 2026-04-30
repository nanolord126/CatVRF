<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FitnessProgressTracking Feature
 *
 * Controls the rollout of fitness-progress-tracking functionality.
 */
final class FitnessProgressTracking extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'fitness';
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
