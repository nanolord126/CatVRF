<?php

declare(strict_types=1);

namespace App\Features;

/**
 * StaffAIRecruiting Feature
 *
 * Controls the rollout of staff-a-i-recruiting functionality.
 */
final class StaffAIRecruiting extends BaseVerticalFeature
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
