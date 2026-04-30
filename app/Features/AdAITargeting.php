<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AdAITargeting Feature
 *
 * Controls the rollout of ad-a-i-targeting functionality.
 */
final class AdAITargeting extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'advertising';
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
