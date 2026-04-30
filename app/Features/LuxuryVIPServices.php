<?php

declare(strict_types=1);

namespace App\Features;

/**
 * LuxuryVIPServices Feature
 *
 * Controls the rollout of luxury-v-i-p-services functionality.
 */
final class LuxuryVIPServices extends BaseVerticalFeature
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
