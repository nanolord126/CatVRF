<?php

declare(strict_types=1);

namespace App\Features;

/**
 * WalletCryptoIntegration Feature
 *
 * Controls the rollout of wallet-crypto-integration functionality.
 */
final class WalletCryptoIntegration extends BaseVerticalFeature
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
