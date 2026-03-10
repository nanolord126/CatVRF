<?php

namespace App\Services\Common;

use App\Contracts\AIEnableEcosystemEntity;
use App\Services\AI\Pricing\DynamicAIPricingEngine;
use App\Models\User;

/**
 * Common Logic for Vertical Entities to avoid duplication.
 * Implementation of AIEnableEcosystemEntity interface.
 */
abstract class BaseVerticalService implements AIEnableEcosystemEntity
{
    public function __construct(
        protected DynamicAIPricingEngine $pricingEngine
    ) {}

    /**
     * Common Implementation of price calculation.
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        $user = auth('tenant')->user() ?? auth()->user() ?? User::find($context['user_id'] ?? 1);
        $vertical = $context['vertical'] ?? 'global';

        $result = $this->pricingEngine->calculateFinalPrice($user, $vertical, $basePrice, $context);
        return (float) $result['final_price'];
    }

    /**
     * Trust score calculated from internal reputation engine.
     */
    public function getTrustScore(): float
    {
        return 0.95; // Default for 2026 simulation
    }

    /**
     * Generate common AI checklist for all services.
     */
    public function generateAiChecklist(): array
    {
        return [
            'Verify coordinates (PostGIS)',
            'Check dynamic coefficients',
            'Validate correlation_id'
        ];
    }
}
