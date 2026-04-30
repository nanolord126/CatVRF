<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConfectioneryCustomization Feature
 *
 * Controls the rollout of confectionery-customization functionality.
 */
final class ConfectioneryCustomization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'confectionery';
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
