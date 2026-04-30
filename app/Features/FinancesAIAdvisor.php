<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FinancesAIAdvisor Feature
 *
 * Controls the rollout of finances-a-i-advisor functionality.
 */
final class FinancesAIAdvisor extends BaseVerticalFeature
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
