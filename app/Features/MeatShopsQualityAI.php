<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MeatShopsQualityAI Feature
 *
 * Controls the rollout of meat-shops-quality-a-i functionality.
 */
final class MeatShopsQualityAI extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'meatshops';
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
