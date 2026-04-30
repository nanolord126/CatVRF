<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GroceryDeliveryScheduling Feature
 *
 * Controls the rollout of grocery-delivery-scheduling functionality.
 */
final class GroceryDeliveryScheduling extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'grocery';
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
