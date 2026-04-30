<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FurnitureARVisualization Feature
 *
 * Controls the rollout of furniture-a-r-visualization functionality.
 */
final class FurnitureARVisualization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'furniture';
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
