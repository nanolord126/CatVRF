<?php

namespace App\Services\AI\Pricing;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Analytics\ConsumerBehaviorLog;
use Illuminate\Support\Str;

class DynamicAIPricingEngine
{
    /**
     * Core Algorithm for Calculating Real-time Dynamic Price based on Customer Profile.
     * Factors: Loyalty (RFM), Elasticity, Device, Competitor Context (simulated), Multi-vertical history.
     */
    public function calculateFinalPrice(User $user, string $vertical, float $basePrice, array $context = []): array
    {
        $multiplier = 1.0;
        $features = [];
        $correlationId = (string) Str::uuid();

        // 1. Get/Create Customer Pricing Profile (Simulated AI Inference)
        $profile = DB::table('customer_ai_pricing_profiles')->where('user_id', $user->id)->first();
        if (!$profile) {
            $profile = $this->inferProfileFromLogs($user);
        }

        // 2. Loyalty Correction (Reward Champions, Discount Lost-at-risk)
        // If user is "At Risk", give a subtle discount 0.95 to convert.
        // If user is "Champion", they are inelastic, can maintain 1.0 or slight premium 1.02.
        $rfm = $context['rfm_segment'] ?? 'N/A';
        if ($rfm === 'At Risk (Used to be frequent)') {
            $multiplier *= 0.93;
            $features['churn_prevention_discount'] = 0.93;
        } elseif ($rfm === 'Champions') {
            $multiplier *= 1.02;
            $features['loyalty_premium_inelastic'] = 1.02;
        }

        // 3. Price Elasticity Logic (Willingness to Pay)
        // Luxury affinity users are less price sensitive.
        if ($profile->luxury_affinity > 0.7) {
            $multiplier *= 1.05;
            $features['luxury_segment_uplift'] = 1.05;
        }

        // 4. Device Context (Simulated iOS/Android correlation with WTP)
        $device = $context['device_os'] ?? 'unknown';
        if ($device === 'ios') {
            $multiplier *= 1.03;
            $features['device_os_premium'] = 1.03;
        }

        // 5. Cross-Vertical Synergy (e.g., Taxi user ordering Food - give cross-sell discount)
        $history = $context['cross_vertical_active'] ?? false;
        if ($history) {
            $multiplier *= 0.95;
            $features['cross_vertical_loyalty'] = 0.95;
        }

        // 6. Time/Demand Surge (Inherited from Taxi logic or global ecosystem load)
        $globalSurge = $context['global_surge'] ?? 1.0;
        $multiplier *= $globalSurge;
        if ($globalSurge > 1.0) $features['global_demand_surge'] = $globalSurge;

        // Finalize
        $finalPrice = round($basePrice * $multiplier, 2);

        // Log calculation for future AI Model training (Feedback Loop)
        DB::table('dynamic_price_calculations')->insert([
            'user_id' => $user->id,
            'vertical' => $vertical,
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'applied_multiplier' => $multiplier,
            'applied_features' => json_encode($features),
            'correlation_id' => $correlationId,
            'created_at' => now(),
        ]);

        return [
            'final_price' => $finalPrice,
            'multiplier' => round($multiplier, 4),
            'features' => $features,
            'correlation_id' => $correlationId
        ];
    }

    /**
     * Infer Pricing Profile from logs if not exists.
     */
    private function inferProfileFromLogs(User $user)
    {
        // Simple heuristic for demo, in production this would be a Python-run embedding model.
        $totalSpend = DB::table('consumer_behavior_logs')
            ->where('user_id', $user->id)
            ->where('event_type', 'purchase')
            ->sum(DB::raw("CAST(JSON_EXTRACT(payload, '$.amount') AS DECIMAL)"));

        $isIos = DB::table('consumer_behavior_logs')
            ->where('user_id', $user->id)
            ->where('payload->source', 'ios_app')
            ->exists();

        DB::table('customer_ai_pricing_profiles')->insert([
            'user_id' => $user->id,
            'price_elasticity' => $totalSpend > 50000 ? 0.8 : 1.2,
            'preferred_device' => $isIos ? 'iOS' : 'Android',
            'luxury_affinity' => $totalSpend > 100000 ? 0.9 : 0.2,
            'is_bargain_hunter' => $totalSpend < 5000,
            'persona_tag' => $totalSpend > 50000 ? 'Affluent' : 'Value Seeker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('customer_ai_pricing_profiles')->where('user_id', $user->id)->first();
    }
}
