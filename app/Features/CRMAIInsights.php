<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CRMAIInsights Feature
 *
 * Controls the rollout of c-r-m-a-i-insights functionality.
 */
final class CRMAIInsights extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'crm';
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
