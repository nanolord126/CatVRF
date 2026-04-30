<?php

declare(strict_types=1);

namespace App\Features;

/**
 * Food Delivery Optimization Feature
 *
 * Controls the rollout of ML-based delivery route optimization for food vertical.
 */
final class FoodDeliveryOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'food';
    }

    protected function getBetaTenants(): array
    {
        return [2, 6, 11, 16];
    }

    protected function canActivate(): bool
    {
        // Verify delivery service availability
        return true;
    }
}
