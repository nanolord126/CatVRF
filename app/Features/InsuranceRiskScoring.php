<?php

declare(strict_types=1);

namespace App\Features;

/**
 * InsuranceRiskScoring Feature
 *
 * Controls the rollout of insurance-risk-scoring functionality.
 */
final class InsuranceRiskScoring extends BaseVerticalFeature
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
