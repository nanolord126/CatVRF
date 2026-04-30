<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TicketsDynamicPricing Feature
 *
 * Controls the rollout of tickets-dynamic-pricing functionality.
 */
final class TicketsDynamicPricing extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'tickets';
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
