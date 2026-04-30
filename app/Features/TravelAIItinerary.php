<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TravelAIItinerary Feature
 *
 * Controls the rollout of travel-a-i-itinerary functionality.
 */
final class TravelAIItinerary extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'travel';
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
