<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HouseholdInventoryOptimization Feature
 *
 * Controls the rollout of household-inventory-optimization functionality.
 */
final class HouseholdInventoryOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'household';
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
