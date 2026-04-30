<?php

declare(strict_types=1);

namespace App\Features;

/**
 * BeautyVirtualTryon Feature
 *
 * Controls the rollout of beauty-virtual-tryon functionality.
 */
final class BeautyVirtualTryon extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'beauty';
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
