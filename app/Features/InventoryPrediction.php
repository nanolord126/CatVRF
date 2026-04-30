<?php

declare(strict_types=1);

namespace App\Features;

/**
 * InventoryPrediction Feature
 *
 * Controls the rollout of inventory-prediction functionality.
 */
final class InventoryPrediction extends BaseVerticalFeature
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
