<?php

declare(strict_types=1);

namespace App\Features;

/**
 * StaffScheduleOptimization Feature
 *
 * Controls the rollout of staff-schedule-optimization functionality.
 */
final class StaffScheduleOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'staff';
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
