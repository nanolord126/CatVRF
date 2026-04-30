<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MarketplaceFraudDetection Feature
 *
 * Controls the rollout of marketplace-fraud-detection functionality.
 */
final class MarketplaceFraudDetection extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'marketplace';
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
