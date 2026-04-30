<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PartySuppliesBundleCreation Feature
 *
 * Controls the rollout of party-supplies-bundle-creation functionality.
 */
final class PartySuppliesBundleCreation extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'partysupplies';
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
