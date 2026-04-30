<?php

declare(strict_types=1);

namespace App\Features;

/**
 * LuxuryAICuration Feature
 *
 * Controls the rollout of luxury-a-i-curation functionality.
 */
final class LuxuryAICuration extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'luxury';
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
