<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CRMAutomation Feature
 *
 * Controls the rollout of c-r-m-automation functionality.
 */
final class CRMAutomation extends BaseVerticalFeature
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
