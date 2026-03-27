<?php

declare(strict_types=1);

namespace App\Services\CarRental;

use App\Models\CarRental\Car;
use App\Models\CarRental\CarType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PricingService (CarRental).
 * Implementation: Layers 4 (Service Logic Layer).
 * Dynamic pricing calculation based on B2B rules, duration, and seasonality.
 */
final readonly class PricingService
{
    /**
     * Standard calculation for rental cost.
     * Incorporates B2B discounts and duration-based tiers.
     */
    public function calculate(Car $car, int $days, bool $isB2B): array
    {
        // 1. Fetch base price from vehicle type (Layer: Domain Data)
        $baseDaily = (int) ($car->type->daily_price_base ?? 0);

        // 2. Duration Multiplier (The longer, the cheaper)
        $durationMultiplier = $this->getDurationMultiplier($days);
        $adjustedDaily = (int) ($baseDaily * $durationMultiplier);

        // 3. B2B Rule (Canon 2026: Commercial logic)
        // B2B gets fixed 15% discount for long-term rentals
        $b2bDiscountMultiplier = ($isB2B && $days >= 7) ? 0.85 : 1.0;
        $finalDaily = (int) ($adjustedDaily * $b2bDiscountMultiplier);

        // 4. Total Calculation
        $total = $finalDaily * $days;

        // 5. Audit Log (Canon Rule 2026: Traceable calculations)
        Log::channel('audit')->info('[CarPricing] Full Calculation Completed', [
            'car_uuid' => $car->uuid,
            'days' => $days,
            'is_b2b' => $isB2B,
            'base_daily' => $baseDaily,
            'final_daily' => $finalDaily,
            'total' => $total,
        ]);

        return [
            'daily_price' => $finalDaily,
            'total_price' => $total,
            'base_daily' => $baseDaily,
            'applied_discount' => (1 - ($finalDaily / $baseDaily)) * 100,
        ];
    }

    /**
     * Logic: Duration-based tiers (Layer 4: Business Rules).
     */
    private function getDurationMultiplier(int $days): float
    {
        return match (true) {
            $days >= 30 => 0.70, // 30% discount for monthly rental
            $days >= 14 => 0.80, // 20% discount
            $days >= 7  => 0.90, // 10% discount
            $days >= 3  => 0.95, // 5% discount
            default     => 1.0,  // Standard daily rate
        };
    }

    /**
     * Logic: Dynamic Surcharge (Seasonality/Availability).
     */
    public function getSurcharge(string $location, \DateTime $date): int
    {
        // Peak season (July-August) has +15% surcharge
        $month = (int) $date->format('m');
        if (in_array($month, [7, 8])) {
            return 115; // 115%
        }

        return 100; // Standard 100%
    }
}
