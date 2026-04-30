<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HomeServicesBookingOptimization Feature
 *
 * Controls the rollout of home-services-booking-optimization functionality.
 */
final class HomeServicesBookingOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'homeservices';
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
