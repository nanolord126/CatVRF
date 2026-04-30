<?php

declare(strict_types=1);

namespace App\Features;

/**
 * EventBudgetOptimization Feature
 *
 * Controls the rollout of event-budget-optimization functionality.
 */
final class EventBudgetOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'event';
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
