<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AutoAIDiagnostics Feature
 *
 * Controls the rollout of auto-a-i-diagnostics functionality.
 */
final class AutoAIDiagnostics extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'auto';
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
