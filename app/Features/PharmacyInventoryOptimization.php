<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PharmacyInventoryOptimization Feature
 *
 * Controls the rollout of pharmacy-inventory-optimization functionality.
 */
final class PharmacyInventoryOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'pharmacy';
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
