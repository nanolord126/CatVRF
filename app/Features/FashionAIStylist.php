<?php

declare(strict_types=1);

namespace App\Features;

/**
 * Fashion AI Stylist Feature
 *
 * Controls the rollout of AI-powered fashion recommendations.
 */
final class FashionAIStylist extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'fashion';
    }

    protected function getBetaTenants(): array
    {
        return [4, 8, 13, 18];
    }
}
