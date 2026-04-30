<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MeatShopsInventoryOptimization Feature
 *
 * Controls the rollout of meat-shops-inventory-optimization functionality.
 */
final class MeatShopsInventoryOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'meatshops';
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
