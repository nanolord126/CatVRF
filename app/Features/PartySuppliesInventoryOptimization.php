<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PartySuppliesInventoryOptimization Feature
 *
 * Controls the rollout of party-supplies-inventory-optimization functionality.
 */
final class PartySuppliesInventoryOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'partysupplies';
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
