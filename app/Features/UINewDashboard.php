<?php

declare(strict_types=1);

namespace App\Features;

/**
 * UINewDashboard Feature
 *
 * Controls the rollout of u-i-new-dashboard functionality.
 */
final class UINewDashboard extends BaseVerticalFeature
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
