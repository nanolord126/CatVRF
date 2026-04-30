<?php

declare(strict_types=1);

namespace App\Features;

/**
 * DeliveryAIScheduling Feature
 *
 * Controls the rollout of delivery-a-i-scheduling functionality.
 */
final class DeliveryAIScheduling extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'delivery';
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
