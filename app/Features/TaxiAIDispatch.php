<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TaxiAIDispatch Feature
 *
 * Controls the rollout of taxi-a-i-dispatch functionality.
 */
final class TaxiAIDispatch extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'taxi';
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
