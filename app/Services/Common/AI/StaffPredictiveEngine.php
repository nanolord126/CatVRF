<?php

namespace App\Services\Common\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Carbon\Carbon;

class StaffPredictiveEngine
{
    /**
     * Forecasts the staffing needs for specific vertical based on historic data, 
     * seasonality, and upcoming events.
     * Use case: "We need 24-30 coaches for the Gym (Sports) next Friday due to a Fitness Marathon."
     */
    public function forecastStaffing(string $vertical, Carbon $targetDate): array
    {
        Log::info("Starting AI Predictive Staffing for {$vertical} on {$targetDate->toDateString()}");

        // Step 1: Baseline demand based on historic records (last 4 similar days)
        $baselineDemand = $this->calculateBaselineDemand($vertical, $targetDate);

        // Step 2: Seasonality & Local Events Multiplier
        $multiplier = $this->getSeasonalityMultiplier($targetDate, $vertical);

        // Step 3: Current Staff Inventory including availability/leave/burnout overrides
        $availableStaff = $this->getCurrentAvailableStaff($vertical, $targetDate);

        $forecastedNeeded = ceil($baselineDemand * $multiplier);
        $shortageCount = max(0, $forecastedNeeded - $availableStaff);
        
        $riskLevel = $this->determineRiskLevel($shortageCount, $forecastedNeeded);

        // Persist prediction for dashboard
        $predictionId = DB::table('staff_demand_predictions')->insertGetId([
            'vertical' => $vertical,
            'role_type' => $this->getDefaultRoleForVertical($vertical),
            'forecast_date' => $targetDate,
            'expected_demand_score' => $baselineDemand * 10,
            'current_staff_count' => $availableStaff,
            'forecasted_staff_needed' => $forecastedNeeded,
            'contributing_factors' => json_encode([
                'baseline' => $baselineDemand,
                'multiplier' => $multiplier,
                'reasoning' => "High seasonality in {$vertical} detected.",
            ]),
            'shortage_probability' => $shortageCount / max(1, $forecastedNeeded),
            'risk_level' => $riskLevel,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'prediction_id' => $predictionId,
            'vertical' => $vertical,
            'forecasted_staff' => $forecastedNeeded,
            'available_staff' => $availableStaff,
            'shortage' => $shortageCount,
            'risk' => $riskLevel,
            'actions' => $this->generateMitigationActions($vertical, $shortageCount, $targetDate),
        ];
    }

    protected function calculateBaselineDemand(string $vertical, Carbon $date): float
    {
        // Analytic logic: Average of 3 last days of same 'day-of-week' type
        // In real system, this would query aggregated order/booking data
        return 15.5; // Dummy baseline for 2026 Simulation
    }

    protected function getSeasonalityMultiplier(Carbon $date, string $vertical): float
    {
        $multiplier = 1.1; // Default growth
        
        // Example: Schools/Education peaks in Sept, Sports in Jan/Feb (New Year resolutions)
        if ($vertical === 'Sports' && $date->month === 1) $multiplier *= 1.5;
        if ($vertical === 'Education' && $date->month === 9) $multiplier *= 1.8;

        return $multiplier;
    }

    protected function getCurrentAvailableStaff(string $vertical, Carbon $date): int
    {
        // Query workforce database minus overrides
        return 12; // Static inventory for simulation
    }

    protected function determineRiskLevel(int $shortage, int $needed): string
    {
        $ratio = $shortage / max(1, $needed);
        if ($ratio > 0.4) return 'CRITICAL';
        if ($ratio > 0.15) return 'HIGH';
        if ($ratio > 0) return 'MEDIUM';
        return 'LOW';
    }

    protected function generateMitigationActions(string $vertical, int $shortage, Carbon $date): array
    {
        $actions = [];
        if ($shortage > 0) {
            // Recommendation 1: Hire via HR Exchange (Internal staff rental)
            $actions[] = [
                'type' => 'HR_EXCHANGE_RENTAL',
                'description' => "Rent {$shortage} temporary staff from other Verticals.",
                'priority' => 'High',
            ];

            // Recommendation 2: Dynamic Pricing (Increase price to lower demand if staff is low)
            $actions[] = [
                'type' => 'DEMAND_THROTTLING',
                'description' => "Increase booking rates by 15% to manage load.",
                'priority' => 'Medium',
            ];
        }

        return $actions;
    }

    private function getDefaultRoleForVertical(string $vertical): string
    {
        return match($vertical) {
            'Sports' => 'Coach',
            'Education' => 'Tutor',
            'Events' => 'Security/Organizer',
            default => 'Staff'
        };
    }
}
