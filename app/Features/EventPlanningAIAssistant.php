<?php

declare(strict_types=1);

namespace App\Features;

/**
 * EventPlanningAIAssistant Feature
 *
 * Controls the rollout of event-planning-a-i-assistant functionality.
 */
final class EventPlanningAIAssistant extends BaseVerticalFeature
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
