<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TicketsFraudDetection Feature
 *
 * Controls the rollout of tickets-fraud-detection functionality.
 */
final class TicketsFraudDetection extends BaseVerticalFeature
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
