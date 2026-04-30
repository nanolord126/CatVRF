<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GardeningPlantCare Feature
 *
 * Controls the rollout of gardening-plant-care functionality.
 */
final class GardeningPlantCare extends BaseVerticalFeature
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
