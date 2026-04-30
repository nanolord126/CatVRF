<?php

declare(strict_types=1);

namespace App\Features;

/**
 * OfficeCateringBudgetOptimization Feature
 *
 * Controls the rollout of office-catering-budget-optimization functionality.
 */
final class OfficeCateringBudgetOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'officecatering';
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
