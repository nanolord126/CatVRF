<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ElectronicsAIComparisons Feature
 *
 * Controls the rollout of electronics-a-i-comparisons functionality.
 */
final class ElectronicsAIComparisons extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'electronics';
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
