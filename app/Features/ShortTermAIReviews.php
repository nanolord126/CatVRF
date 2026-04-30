<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ShortTermAIReviews Feature
 *
 * Controls the rollout of short-term-a-i-reviews functionality.
 */
final class ShortTermAIReviews extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'shortterm';
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
