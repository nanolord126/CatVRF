<?php

declare(strict_types=1);

namespace App\Features;

/**
 * Real Estate AI Valuation Feature
 *
 * Controls the rollout of AI-powered property valuation.
 */
final class RealEstateAIValuation extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'realestate';
    }

    protected function getBetaTenants(): array
    {
        return [3, 7, 12, 17];
    }
}
