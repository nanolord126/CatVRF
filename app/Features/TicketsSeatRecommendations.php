<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TicketsSeatRecommendations Feature
 *
 * Controls the rollout of tickets-seat-recommendations functionality.
 */
final class TicketsSeatRecommendations extends BaseVerticalFeature
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
