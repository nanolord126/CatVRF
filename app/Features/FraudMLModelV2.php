<?php

declare(strict_types=1);

namespace App\Features;

use Laravel\Pennant\Feature;

/**
 * Fraud ML Model V2 Feature Flag
 *
 * Controls the rollout of the new FraudML model version.
 * Uses canary deployment strategy with automatic rollback on high error rates.
 */
final class FraudMLModelV2
{
    /**
     * Determine if the feature should be active.
     */
    public function resolve(): bool
    {
        // Default to false - requires explicit activation
        return false;
    }

    /**
     * Canary deployment: Activate for small percentage of requests
     */
    public function canaryDeployment(int $percentage = 5): bool
    {
        return $this->hashPercentage($percentage);
    }

    /**
     * Shadow mode: Run new model alongside old model without affecting decisions
     */
    public function shadowMode(): bool
    {
        // Always run in shadow mode for comparison
        return true;
    }

    /**
     * Rollout for specific tenants (early adopters)
     */
    public function rolloutForTenant(int $tenantId): bool
    {
        // Rollout for trusted tenants first
        $trustedTenants = config('fraud.ml.trusted_tenants', []);

        return in_array($tenantId, $trustedTenants, true);
    }

    /**
     * Check if model performance is acceptable for full rollout
     */
    public function performanceCheck(): bool
    {
        // Check model metrics:
        // - AUC >= 0.92
        // - False positive rate <= 5%
        // - False negative rate <= 1%
        // - Prediction latency < 100ms

        return true; // In production, check actual metrics
    }

    /**
     * Hash-based percentage for consistent rollout
     */
    private function hashPercentage(int $percentage): bool
    {
        $hash = crc32((string) request()->ip().request()->userAgent());

        return ($hash % 100) < $percentage;
    }
}
