<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CollectiblesAuthentication Feature
 *
 * Controls the rollout of collectibles-authentication functionality.
 */
final class CollectiblesAuthentication extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'collectibles';
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
