<?php

declare(strict_types=1);

namespace App\Features;

/**
 * UIMobileOptimizations Feature
 *
 * Controls the rollout of u-i-mobile-optimizations functionality.
 */
final class UIMobileOptimizations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'ui';
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
