<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FinancesInvestmentRecommendations Feature
 *
 * Controls the rollout of finances-investment-recommendations functionality.
 */
final class FinancesInvestmentRecommendations extends BaseVerticalFeature
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
