<?php

declare(strict_types=1);

namespace App\Features;

/**
 * WalletMultiCurrency Feature
 *
 * Controls the rollout of wallet-multi-currency functionality.
 */
final class WalletMultiCurrency extends BaseVerticalFeature
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
