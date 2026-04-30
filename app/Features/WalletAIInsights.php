<?php

declare(strict_types=1);

namespace App\Features;

/**
 * WalletAIInsights Feature
 *
 * Controls the rollout of wallet-a-i-insights functionality.
 */
final class WalletAIInsights extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'wallet';
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
