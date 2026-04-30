<?php

declare(strict_types=1);

namespace App\Features;

/**
 * InsuranceClaimsAutomation Feature
 *
 * Controls the rollout of insurance-claims-automation functionality.
 */
final class InsuranceClaimsAutomation extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'insurance';
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
