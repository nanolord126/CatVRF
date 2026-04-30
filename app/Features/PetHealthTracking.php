<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PetHealthTracking Feature
 *
 * Controls the rollout of pet-health-tracking functionality.
 */
final class PetHealthTracking extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'pet';
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
