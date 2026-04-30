<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GardeningSeasonalPlanning Feature
 *
 * Controls the rollout of gardening-seasonal-planning functionality.
 */
final class GardeningSeasonalPlanning extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'gardening';
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
