<?php

declare(strict_types=1);

namespace App\Features;

/**
 * UIDarkMode Feature
 *
 * Controls the rollout of u-i-dark-mode functionality.
 */
final class UIDarkMode extends BaseVerticalFeature
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
