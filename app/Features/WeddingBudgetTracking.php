<?php

declare(strict_types=1);

namespace App\Features;

/**
 * WeddingBudgetTracking Feature
 *
 * Controls the rollout of wedding-budget-tracking functionality.
 */
final class WeddingBudgetTracking extends BaseVerticalFeature
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
