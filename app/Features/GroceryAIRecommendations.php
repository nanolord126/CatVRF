<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GroceryAIRecommendations Feature
 *
 * Controls the rollout of grocery-a-i-recommendations functionality.
 */
final class GroceryAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'grocery';
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
