<?php

declare(strict_types=1);

namespace App\Features;

/**
 * WeddingVendorRecommendations Feature
 *
 * Controls the rollout of wedding-vendor-recommendations functionality.
 */
final class WeddingVendorRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'wedding';
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
