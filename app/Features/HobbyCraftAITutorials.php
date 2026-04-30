<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HobbyCraftAITutorials Feature
 *
 * Controls the rollout of hobby-craft-a-i-tutorials functionality.
 */
final class HobbyCraftAITutorials extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'hobbycraft';
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
