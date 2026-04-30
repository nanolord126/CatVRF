<?php

declare(strict_types=1);

namespace App\Features;

/**
 * InsuranceAIUnderwriting Feature
 *
 * Controls the rollout of insurance-a-i-underwriting functionality.
 */
final class InsuranceAIUnderwriting extends BaseVerticalFeature
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
