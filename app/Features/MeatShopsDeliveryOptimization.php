<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MeatShopsDeliveryOptimization Feature
 *
 * Controls the rollout of meat-shops-delivery-optimization functionality.
 */
final class MeatShopsDeliveryOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'meatshops';
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
