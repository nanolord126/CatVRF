<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FinancesBudgetOptimization Feature
 *
 * Controls the rollout of finances-budget-optimization functionality.
 */
final class FinancesBudgetOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'finances';
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
