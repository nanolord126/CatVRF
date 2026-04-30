<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HobbyCraftMaterialOptimization Feature
 *
 * Controls the rollout of hobby-craft-material-optimization functionality.
 */
final class HobbyCraftMaterialOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'hobbycraft';
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
