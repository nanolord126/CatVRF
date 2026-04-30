<?php

declare(strict_types=1);

namespace App\Features;

/**
 * InventoryOptimization Feature
 *
 * Controls the rollout of inventory-optimization functionality.
 */
final class InventoryOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'inventory';
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
