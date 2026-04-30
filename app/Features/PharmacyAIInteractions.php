<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PharmacyAIInteractions Feature
 *
 * Controls the rollout of pharmacy-a-i-interactions functionality.
 */
final class PharmacyAIInteractions extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'pharmacy';
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
