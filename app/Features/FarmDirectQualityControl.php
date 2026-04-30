<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FarmDirectQualityControl Feature
 *
 * Controls the rollout of farm-direct-quality-control functionality.
 */
final class FarmDirectQualityControl extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'farmdirect';
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
