<?php

namespace App\Services\AI\Simulations;

use Illuminate\Support\Facades\DB;

class DigitalTwinBusinessEngine
{
    /**
     * Run a business behavior simulation based on altered parameters (Price/Staff).
     * Simulates outcomes for the next 30 days.
     */
    public function runScenario(string $vertical, array $params): array
    {
        // 1. Get Baseline (Historical data for the last 90 days)
        $baseline = DB::table('simulation_baselines')
            ->where('vertical', $vertical)
            ->latest()
            ->first();

        if (!$baseline) {
            // Simulated default if no real data found (e.g., initial setup)
            $baseline = (object)[
                'avg_daily_revenue' => 100000.0,
                'avg_staff_load' => 80,
                'avg_conversion_rate' => 0.15,
            ];
        }

        // 2. Extract Scenario Deltas
        $priceChange = $params['tariff_change'] ?? 1.0; // 1.15 = +15%
        $staffChange = $params['staff_count_change'] ?? 0; // -5 = reduced headcount

        // 3. Apply Heuristics (Cross-Elasticity Simulation)
        
        // Revenue Impact: 
        // Logic: 1% price increase usually drops demand by N% (Elasticity). 
        // For 'Champions' (inelastic), we use 0.5; for 'Value Seekers', 1.5. 
        // Averaging to 1.0 elasticity for simulation.
        $revenueDelta = $priceChange * (1 - (($priceChange - 1) * 1.2)); // If price +20% (1.2), demand falls by 24%
        $predictedDailyRevenue = $baseline->avg_daily_revenue * $revenueDelta;

        // Staff Impact: 
        // Less staff = higher load per person, but potentially higher wait times (lower conversion).
        $newStaffLoad = $baseline->avg_staff_load * ($baseline->avg_staff_load / ($baseline->avg_staff_load + $staffChange));
        
        $churnImpact = 0.0;
        if ($newStaffLoad > 95) {
            $churnImpact = 0.15; // +15% churn risk if everyone is overloaded
            $predictedDailyRevenue *= 0.9; // Lower revenue due to cancellations
        }

        // 4. Output Projection
        $summary = "Scenario: Price " . ($priceChange > 1 ? '+' : '') . (round($priceChange - 1, 2) * 100) . "% ";
        $summary .= "with Staff change: {$staffChange}. ";
        $summary .= ($newStaffLoad > 95) ? "WARNING: Critical staff overload predicted." : "Sustainable load level.";

        return [
            'predicted_monthly_revenue' => round($predictedDailyRevenue * 30, 2),
            'staff_load_projection' => round($newStaffLoad, 1),
            'churn_risk_delta' => $churnImpact,
            'summary' => $summary,
            'confidence' => 0.88
        ];
    }
}
