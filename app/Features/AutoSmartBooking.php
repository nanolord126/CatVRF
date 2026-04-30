<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AutoSmartBooking Feature
 *
 * Controls the rollout of auto-smart-booking functionality.
 */
final class AutoSmartBooking extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'auto';
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
