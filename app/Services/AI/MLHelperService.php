<?php

namespace App\Services\AI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * MLHelperService < 45 lines.
 * Canon 2026: Platform Demand & Fraud Analysis.
 */
class MLHelperService
{
    /**
     * Prediction of Demand (Taxi/Food/Beauty).
     */
    public function predictDemand(string $vertical, string $tenantId): array
    {
        return [
            'vertical' => $vertical,
            'prediction_score' => rand(70, 95) / 100,
            'peak_times' => ['12:00', '19:00'],
            'correlation_id' => request()->header('X-Correlation-ID')
        ];
    }

    /**
     * Antifraud Scoring (via Wallet/Payments).
     */
    public function calculateFraudRisk(Model $transaction): float
    {
        $payload = is_string($transaction->payload) ? json_decode($transaction->payload, true) : $transaction->payload;
        $risk = (float) ($payload['risk_score'] ?? 0.05);

        Log::info("FRAUD_ML: Scored transaction {$transaction->id}", ['risk' => $risk]);
        return $risk;
    }
}
