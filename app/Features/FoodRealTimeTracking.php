<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FoodRealTimeTracking Feature
 *
 * Controls the rollout of food-real-time-tracking functionality.
 */
final class FoodRealTimeTracking extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'food';
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
