<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FashionTrendPrediction Feature
 *
 * Controls the rollout of fashion-trend-prediction functionality.
 */
final class FashionTrendPrediction extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'fashion';
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
