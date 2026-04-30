<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GeoLogisticsDemandForecasting Feature
 *
 * Controls the rollout of geo-logistics-demand-forecasting functionality.
 */
final class GeoLogisticsDemandForecasting extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'geo';
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
