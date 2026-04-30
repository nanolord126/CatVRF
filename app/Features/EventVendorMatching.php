<?php

declare(strict_types=1);

namespace App\Features;

/**
 * EventVendorMatching Feature
 *
 * Controls the rollout of event-vendor-matching functionality.
 */
final class EventVendorMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'event';
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
