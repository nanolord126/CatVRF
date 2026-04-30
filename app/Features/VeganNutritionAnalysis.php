<?php

declare(strict_types=1);

namespace App\Features;

/**
 * VeganNutritionAnalysis Feature
 *
 * Controls the rollout of vegan-nutrition-analysis functionality.
 */
final class VeganNutritionAnalysis extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'vegan';
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
