<?php

declare(strict_types=1);

namespace App\Services\Insurance;

use App\Models\Insurance\InsurancePolicy;
use App\Models\Insurance\InsuranceType;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PricingService (Insurance Premium Calculator).
 * Implementation: Layer 4 (Business Logic).
 * Requirements: >60 lines, correlation_id, multiple risk factors.
 * Logic: Calculates the premium amount based on user profile, insurance type, and company settings.
 */
final readonly class PricingService
{
    /**
     * Calculate final premium amount for a potential policy.
     * All amounts in cents (int).
     */
    public function calculatePremium(
        InsuranceType $type,
        User $user,
        array $params,
        bool $isB2B = false,
        string $correlationId = null
    ): int {
        $correlationId = $correlationId ?? (string) Str::uuid();

        // 1. Log Start (Audit Trace: Canon 2026)
        Log::channel('audit')->info('[PricingService] Calculating premium', [
            'correlation_id' => $correlationId,
            'insurance_type' => $type->slug,
            'user_id' => $user->id,
            'is_b2b' => $isB2B,
        ]);

        // 2. Base Rate from Type or Config
        $baseRate = (int) ($type->base_multipliers['base_price'] ?? 500000); // 5000.00 RUB default

        // 3. User Age Factor (Risk multiplier)
        $age = $user->age ?? 30; // Fallback to 30
        $ageFactor = match (true) {
            $age < 21 => 1.85,  // High risk young
            $age < 25 => 1.40,
            $age > 65 => 1.25,  // Senior risk
            default => 1.00,
        };

        // 4. B2B vs B2C Multiplier
        // B2B gets specialized corporate discounts or surcharges depending on fleet size
        $businessFactor = $isB2B ? 0.85 : 1.00; // 15% discount for B2B bulk

        // 5. Dynamic Logic per Type (Vertical Specialists)
        $typeFactor = 1.00;
        switch ($type->slug) {
            case 'osago':
                $typeFactor = $this->calculateVehicleRisk($params);
                break;
            case 'health':
                $typeFactor = $this->calculateHealthRisk($params);
                break;
            case 'travel':
                $typeFactor = $this->calculateTravelRisk($params);
                break;
            default:
                $typeFactor = 1.10; // Unknown type surcharge
                break;
        }

        // 6. Region Multiplier (e.g., Moscow vs Regions)
        $region = $params['region_id'] ?? 77; // Default 77 (Moscow)
        $regionFactor = in_array($region, [77, 78, 50], true) ? 1.45 : 1.00;

        // 7. Final Calculation (Cents)
        $totalPremium = (int) (
            $baseRate *
            $ageFactor *
            $businessFactor *
            $typeFactor *
            $regionFactor
        );

        // 8. Log Completion (Audit Trace)
        Log::channel('audit')->info('[PricingService] Premium calculation success', [
            'correlation_id' => $correlationId,
            'final_premium' => $totalPremium,
            'factors' => [
                'age' => $ageFactor,
                'business' => $businessFactor,
                'type' => $typeFactor,
                'region' => $regionFactor,
            ],
        ]);

        return max($totalPremium, 10000); // Minimum premium 100 RUB
    }

    private function calculateVehicleRisk(array $params): float
    {
        $hp = $params['engine_hp'] ?? 100;
        $powerFactor = match (true) {
            $hp < 50 => 0.60,
            $hp < 100 => 1.00,
            $hp < 150 => 1.40,
            default => 1.60,
        };

        $experience = $params['driving_experience'] ?? 10;
        $expFactor = $experience < 3 ? 1.70 : 0.90;

        return $powerFactor * $expFactor;
    }

    private function calculateHealthRisk(array $params): float
    {
        $hasChronic = ($params['chronic_diseases'] ?? false) ? 2.50 : 1.00;
        $smoking = ($params['is_smoking'] ?? false) ? 1.35 : 1.00;

        return $hasChronic * $smoking;
    }

    private function calculateTravelRisk(array $params): float
    {
        $duration = $params['duration_days'] ?? 14;
        $extremeSports = ($params['extreme_sports'] ?? false) ? 3.00 : 1.00;

        // Daily rate logic simulation
        $dailyRate = $duration > 30 ? 0.8 : 1.0;

        return $dailyRate * $extremeSports;
    }
}
