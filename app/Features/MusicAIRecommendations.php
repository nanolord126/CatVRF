<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MusicAIRecommendations Feature
 *
 * Controls the rollout of music-a-i-recommendations functionality.
 */
final class MusicAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'music';
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
