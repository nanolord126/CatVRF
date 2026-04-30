<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FashionVirtualFitting Feature
 *
 * Controls the rollout of fashion-virtual-fitting functionality.
 */
final class FashionVirtualFitting extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'fashion';
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
