<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\Analytics\ConsumerBehaviorLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsumerBehaviorAIService
{
    /**
     * Log user behavior event with correlation ID for AI analysis.
     */
    public function logEvent(User $user, string $eventType, ?string $entityType = null, ?int $entityId = null, array $payload = []): void
    {
        ConsumerBehaviorLog::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => $payload,
            'correlation_id' => $payload['correlation_id'] ?? (string) Str::uuid(),
        ]);
    }

    /**
     * Perform RFM Analysis (Recency, Frequency, Monetary) on a user.
     * Simulated calculation logic.
     */
    public function calculateRFM(User $user): array
    {
        // Calculate Recency Score (days since last purchase/event)
        $lastActivity = ConsumerBehaviorLog::where('user_id', $user->id)
            ->whereIn('event_type', ['purchase', 'order_complete', 'appointment_finish'])
            ->latest('created_at')
            ->first();

        $recencyDays = $lastActivity ? now()->diffInDays($lastActivity->created_at) : 365;
        $recencyScore = $recencyDays <= 30 ? 5 : ($recencyDays <= 60 ? 4 : ($recencyDays <= 90 ? 3 : ($recencyDays <= 180 ? 2 : 1)));

        // Calculate Frequency Score (count of successful events in last 12 months)
        $frequencyCount = ConsumerBehaviorLog::where('user_id', $user->id)
            ->whereIn('event_type', ['purchase', 'order_complete', 'appointment_finish'])
            ->where('created_at', '>=', now()->subYear())
            ->count();

        $frequencyScore = $frequencyCount >= 50 ? 5 : ($frequencyCount >= 20 ? 4 : ($frequencyCount >= 10 ? 3 : ($frequencyCount >= 5 ? 2 : 1)));

        // Calculate Monetary Score (total spend simulated from payload)
        // In real app, we'd query payments/orders table
        $monetaryTotal = ConsumerBehaviorLog::where('user_id', $user->id)
            ->where('event_type', 'purchase')
            ->sum(DB::raw("CAST(JSON_EXTRACT(payload, '$.amount') AS DECIMAL)"));

        $monetaryScore = $monetaryTotal >= 50000 ? 5 : ($monetaryTotal >= 20000 ? 4 : ($monetaryTotal >= 10000 ? 3 : ($monetaryTotal >= 5000 ? 2 : 1)));

        $segment = $this->determineSegment($recencyScore, $frequencyScore, $monetaryScore);

        return [
            'recency' => $recencyScore,
            'frequency' => $frequencyScore,
            'monetary' => $monetaryScore,
            'segment' => $segment,
            'raw_monetary' => $monetaryTotal
        ];
    }

    private function determineSegment($r, $f, $m): string
    {
        $avg = ($r + $f + $m) / 3;

        if ($r >= 4 && $f >= 4 && $m >= 4) return 'Champions';
        if ($avg >= 4) return 'Loyal Customers';
        if ($r >= 4 && $f <= 2) return 'New Customers';
        if ($r <= 2 && $f >= 4) return 'At Risk (Used to be frequent)';
        if ($r <= 1) return 'Lost Customers';
        
        return 'Needs Attention';
    }

    /**
     * Generate Predictive Personalized Offer based on LTV/Churn prediction (Simulated AI Model).
     */
    public function generatePersonalizedAIOffer(User $user): array
    {
        $rfm = $this->calculateRFM($user);
        
        // Simulating AI Churn Prediction Logic
        $churnProb = 0.0;
        if ($rfm['segment'] === 'At Risk (Used to be frequent)') {
            $churnProb = 0.85;
        } elseif ($rfm['segment'] === 'Loyal Customers') {
            $churnProb = 0.15;
        }

        // Simulating OpenAI Recommendation Logic
        $recommendation = "Customer is in segment {$rfm['segment']}. ";
        $offerPrompt = "";

        if ($churnProb > 0.7) {
            $recommendation .= "High risk of churn. Apply heavy retention discount.";
            $offerPrompt = "50% Discount on next Taxi or Restaurant order";
        } else {
            $recommendation .= "Healthy engagement. Upsell to High-Value Health Clinic service.";
            $offerPrompt = "15% off at Hair and Beauty Clinics";
        }

        return [
            'user_id' => $user->id,
            'churn_probability' => $churnProb,
            'advice' => $recommendation,
            'suggested_offer' => $offerPrompt,
            'rfm' => $rfm
        ];
    }
}
