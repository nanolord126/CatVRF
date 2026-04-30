<?php

declare(strict_types=1);

namespace App\Features;

/**
 * BeautyBookingOptimization Feature
 *
 * Controls the rollout of beauty-booking-optimization functionality.
 */
final class BeautyBookingOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'beauty';
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
