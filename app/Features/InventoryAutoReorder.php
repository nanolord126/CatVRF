<?php

declare(strict_types=1);

namespace App\Features;

/**
 * InventoryAutoReorder Feature
 *
 * Controls the rollout of inventory-auto-reorder functionality.
 */
final class InventoryAutoReorder extends BaseVerticalFeature
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
