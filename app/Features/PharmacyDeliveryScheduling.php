<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PharmacyDeliveryScheduling Feature
 *
 * Controls the rollout of pharmacy-delivery-scheduling functionality.
 */
final class PharmacyDeliveryScheduling extends BaseVerticalFeature
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
