<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ToysSafetyCheck Feature
 *
 * Controls the rollout of toys-safety-check functionality.
 */
final class ToysSafetyCheck extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'toys';
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
