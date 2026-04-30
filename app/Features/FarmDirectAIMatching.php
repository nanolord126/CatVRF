<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FarmDirectAIMatching Feature
 *
 * Controls the rollout of farm-direct-a-i-matching functionality.
 */
final class FarmDirectAIMatching extends BaseVerticalFeature
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
