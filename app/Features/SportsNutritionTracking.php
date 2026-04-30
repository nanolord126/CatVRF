<?php

declare(strict_types=1);

namespace App\Features;

/**
 * SportsNutritionTracking Feature
 *
 * Controls the rollout of sports-nutrition-tracking functionality.
 */
final class SportsNutritionTracking extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'sports';
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
