<?php

declare(strict_types=1);

namespace App\Features;

/**
 * LuxuryPersonalStylist Feature
 *
 * Controls the rollout of luxury-personal-stylist functionality.
 */
final class LuxuryPersonalStylist extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'luxury';
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
