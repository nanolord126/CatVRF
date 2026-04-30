<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class TenderMLRiskAssessmentService
{
    use WithTelemetry;

    public function __construct(
        private readonly WalletService $walletService,
        private readonly FraudControlService $fraudControl
    ) {}

    /**
     * Perform comprehensive ML risk assessment for tender guarantee
     */
    public function assessRisk(
        int $businessId,
        int $tenantId,
        int $verticalId,
        float $tenderAmount
    ): array {
        return $this->withSpan('ml.tender.risk.assessment', function () use (
            $businessId,
            $tenantId,
            $verticalId,
            $tenderAmount
        ) {
            $config = $this->getVerticalConfig($verticalId);
            
            // Extract features
            $features = $this->extractFeatures($businessId, $tenantId, $verticalId, $tenderAmount, $config);
            
            // Calculate weighted score
            $score = $this->calculateWeightedScore($features, $config['ml_weights']);
            
            // Apply seasonal adjustment
            $score = $this->applySeasonalAdjustment($score, $config['seasonality']);
            
            // Determine risk level
            $riskLevel = $this->determineRiskLevel($score);
            
            // Check eligibility for guarantee
            $eligible = $this->checkGuaranteeEligibility($score, $riskLevel, $config);
            
            return [
                'score' => round($score, 2),
                'risk_level' => $riskLevel,
                'eligible' => $eligible,
                'features' => $features,
                'vertical' => $config['name'],
                'thresholds' => $this->getThresholds(),
            ];
        });
    }

    /**
     * Extract features for ML assessment
     */
    private function extractFeatures(
        int $businessId,
        int $tenantId,
        int $verticalId,
        float $tenderAmount,
        array $config
    ): array {
        $balance = $this->walletService->getBalance($businessId, $tenantId);
        $balanceRatio = $balance / max($tenderAmount, 1);
        
        // Transaction history
        $transactionHistory = $this->getTransactionHistoryScore($businessId, $tenantId);
        
        // Vertical experience
        $verticalExperience = $this->getVerticalExperience($businessId, $verticalId);
        
        // Payment reliability
        $paymentReliability = $this->getPaymentReliability($businessId, $tenantId);
        
        // Geographic risk (if applicable)
        $geographicRisk = $this->getGeographicRisk($businessId, $config);
        
        // Reputation score (if applicable)
        $reputationScore = $this->getReputationScore($businessId, $verticalId);
        
        // Seasonal factor
        $seasonalFactor = $this->getSeasonalFactor($config);
        
        // Legal compliance (for high-risk verticals like RealEstate)
        $legalCompliance = $this->getLegalCompliance($businessId, $config);

        return [
            'balance_ratio' => min(100, $balanceRatio * 100),
            'transaction_history' => $transactionHistory,
            'vertical_experience' => $verticalExperience,
            'payment_reliability' => $paymentReliability,
            'geographic_risk' => $geographicRisk,
            'reputation_score' => $reputationScore,
            'seasonal_factor' => $seasonalFactor,
            'legal_compliance' => $legalCompliance,
            'balance' => $balance,
            'balance_ratio_raw' => $balanceRatio,
        ];
    }

    /**
     * Calculate weighted score based on vertical-specific weights
     */
    private function calculateWeightedScore(array $features, array $weights): float
    {
        $score = 0;
        $totalWeight = 0;

        foreach ($weights as $feature => $weight) {
            if (isset($features[$feature])) {
                $score += $features[$feature] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? $score / $totalWeight : 50;
    }

    /**
     * Apply seasonal adjustment to score
     */
    private function applySeasonalAdjustment(float $score, array $seasonality): float
    {
        $quarter = 'Q' . ceil(now()->month / 3);
        $factor = $seasonality[$quarter] ?? 1.0;
        
        // During high season, be more lenient (higher effective score)
        // During low season, be stricter (lower effective score)
        return $score * $factor;
    }

    /**
     * Determine risk level based on score
     */
    private function determineRiskLevel(float $score): string
    {
        if ($score >= 90) {
            return 'low';
        } elseif ($score >= 70) {
            return 'low';
        } elseif ($score >= 60) {
            return 'medium';
        } elseif ($score >= 40) {
            return 'high';
        } else {
            return 'critical';
        }
    }

    /**
     * Check eligibility for platform guarantee
     */
    private function checkGuaranteeEligibility(float $score, string $riskLevel, array $config): bool
    {
        $thresholds = config('tender-vertical-risks.thresholds.guarantee_eligibility');
        
        // High-risk verticals require higher scores
        $baseThreshold = $thresholds[$config['base_risk_level']] ?? 70;
        
        return $score >= $baseThreshold && $riskLevel !== 'critical';
    }

    /**
     * Get transaction history score (0-100)
     */
    private function getTransactionHistoryScore(int $businessId, int $tenantId): float
    {
        $cacheKey = "tender_ml:tx_history:{$businessId}:{$tenantId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($businessId, $tenantId) {
            $last90Days = now()->subDays(90);
            
            $stats = DB::table('transactions')
                ->where('user_id', $businessId)
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $last90Days)
                ->selectRaw('
                    COUNT(*) as total_transactions,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as successful_transactions,
                    COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_transactions,
                    SUM(amount) as total_volume
                ')
                ->first();
            
            if (!$stats || $stats->total_transactions === 0) {
                return 30; // Low score for no history
            }
            
            $successRate = ($stats->successful_transactions / $stats->total_transactions) * 100;
            $volumeScore = min(100, $stats->total_volume / 1000000 * 100); // Normalize by 1M
            
            return ($successRate * 0.7) + ($volumeScore * 0.3);
        });
    }

    /**
     * Get vertical experience score (0-100)
     */
    private function getVerticalExperience(int $businessId, int $verticalId): float
    {
        $cacheKey = "tender_ml:vertical_exp:{$businessId}:{$verticalId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($businessId, $verticalId) {
            $tenderStats = DB::table('tender_statistics')
                ->where('supplier_id', $businessId)
                ->where('vertical_id', $verticalId)
                ->first();
            
            if (!$tenderStats) {
                return 20; // No experience
            }
            
            $winRate = $tenderStats->total_participated > 0 
                ? ($tenderStats->total_won / $tenderStats->total_participated) * 100 
                : 0;
            
            $volumeScore = min(100, $tenderStats->total_value_won / 10000000 * 100); // Normalize by 10M
            
            return ($winRate * 0.6) + ($volumeScore * 0.4);
        });
    }

    /**
     * Get payment reliability score (0-100)
     */
    private function getPaymentReliability(int $businessId, int $tenantId): float
    {
        $cacheKey = "tender_ml:payment_reliability:{$businessId}:{$tenantId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($businessId, $tenantId) {
            $last180Days = now()->subDays(180);
            
            $payments = DB::table('payments')
                ->where('user_id', $businessId)
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $last180Days)
                ->selectRaw('
                    COUNT(*) as total_payments,
                    COUNT(CASE WHEN status = "success" THEN 1 END) as successful_payments,
                    COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_payments,
                    AVG(CASE WHEN paid_at IS NOT NULL THEN DATEDIFF(paid_at, created_at) END) as avg_payment_days
                ')
                ->first();
            
            if (!$payments || $payments->total_payments === 0) {
                return 50; // Neutral score
            }
            
            $successRate = ($payments->successful_payments / $payments->total_payments) * 100;
            $timelinessScore = max(0, 100 - ($payments->avg_payment_days ?? 0));
            
            return ($successRate * 0.7) + ($timelinessScore * 0.3);
        });
    }

    /**
     * Get geographic risk score (0-100)
     */
    private function getGeographicRisk(int $businessId, array $config): float
    {
        // For now, return neutral score
        // TODO: Integrate with geolocation risk assessment
        return 80;
    }

    /**
     * Get reputation score (0-100)
     */
    private function getReputationScore(int $businessId, int $verticalId): float
    {
        $cacheKey = "tender_ml:reputation:{$businessId}:{$verticalId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($businessId, $verticalId) {
            $stats = DB::table('tender_statistics')
                ->where('supplier_id', $businessId)
                ->where('vertical_id', $verticalId)
                ->first();
            
            if (!$stats || $stats->total_reviews === 0) {
                return 50; // Neutral score
            }
            
            return $stats->average_rating * 20; // Convert 1-5 to 0-100
        });
    }

    /**
     * Get seasonal factor
     */
    private function getSeasonalFactor(array $config): float
    {
        $quarter = 'Q' . ceil(now()->month / 3);
        return $config['seasonality'][$quarter] ?? 1.0;
    }

    /**
     * Get legal compliance score
     */
    private function getLegalCompliance(int $businessId, array $config): float
    {
        // Only applicable for high-risk verticals like RealEstate
        if (!in_array('legal_compliance', $config['risk_factors'])) {
            return 100;
        }
        
        // TODO: Integrate with legal compliance checking
        return 80;
    }

    /**
     * Get vertical configuration
     */
    private function getVerticalConfig(int $verticalId): array
    {
        $config = config('tender-vertical-risks.verticals');
        
        return $config[$verticalId] ?? $config['default'];
    }

    /**
     * Get thresholds
     */
    private function getThresholds(): array
    {
        return config('tender-vertical-risks.thresholds');
    }

    /**
     * Check for vertical-specific fraud indicators
     */
    public function checkVerticalFraudIndicators(
        int $businessId,
        int $verticalId,
        float $amount,
        array $context = []
    ): array {
        $config = $this->getVerticalConfig($verticalId);
        $indicators = $config['fraud_indicators'];
        $detected = [];

        foreach ($indicators as $indicator => $enabled) {
            if (!$enabled) {
                continue;
            }

            $detected[] = $this->checkIndicator($indicator, $businessId, $verticalId, $amount, $context);
        }

        return [
            'has_risk' => collect($detected)->filter(fn($i) => $i['detected'])->isNotEmpty(),
            'indicators' => $detected,
        ];
    }

    /**
     * Check specific fraud indicator
     */
    private function checkIndicator(
        string $indicator,
        int $businessId,
        int $verticalId,
        float $amount,
        array $context
    ): array {
        return match($indicator) {
            'sudden_large_orders' => $this->checkSuddenLargeOrders($businessId, $amount),
            'price_anomaly' => $this->checkPriceAnomaly($verticalId, $amount),
            'payment_delays' => $this->checkPaymentDelays($businessId),
            'high_return_rate' => $this->checkReturnRate($businessId),
            'fake_reviews' => $this->checkFakeReviews($businessId),
            default => ['indicator' => $indicator, 'detected' => false, 'confidence' => 0],
        };
    }

    /**
     * Check for sudden large orders
     */
    private function checkSuddenLargeOrders(int $businessId, float $amount): array
    {
        $avgAmount = DB::table('transactions')
            ->where('user_id', $businessId)
            ->where('created_at', '>=', now()->subDays(90))
            ->avg('amount') ?? 0;

        $threshold = $avgAmount * 3; // 3x average
        $detected = $amount > $threshold && $threshold > 0;

        return [
            'indicator' => 'sudden_large_orders',
            'detected' => $detected,
            'confidence' => $detected ? 0.8 : 0,
            'details' => [
                'current_amount' => $amount,
                'average_amount' => $avgAmount,
                'threshold' => $threshold,
            ],
        ];
    }

    /**
     * Check for price anomaly
     */
    private function checkPriceAnomaly(int $verticalId, float $amount): array
    {
        $config = $this->getVerticalConfig($verticalId);
        $typicalMax = $config['typical_amount_max'];
        
        $detected = $amount > $typicalMax * 2;

        return [
            'indicator' => 'price_anomaly',
            'detected' => $detected,
            'confidence' => $detected ? 0.7 : 0,
            'details' => [
                'current_amount' => $amount,
                'typical_max' => $typicalMax,
            ],
        ];
    }

    /**
     * Check for payment delays
     */
    private function checkPaymentDelays(int $businessId): array
    {
        $delayedPayments = DB::table('payments')
            ->where('user_id', $businessId)
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        $detected = $delayedPayments > 3;

        return [
            'indicator' => 'payment_delays',
            'detected' => $detected,
            'confidence' => $detected ? 0.9 : 0,
            'details' => [
                'delayed_count' => $delayedPayments,
            ],
        ];
    }

    /**
     * Check for high return rate
     */
    private function checkReturnRate(int $businessId): array
    {
        // TODO: Implement return rate checking
        return [
            'indicator' => 'high_return_rate',
            'detected' => false,
            'confidence' => 0,
        ];
    }

    /**
     * Check for fake reviews
     */
    private function checkFakeReviews(int $businessId): array
    {
        // TODO: Implement fake review detection
        return [
            'indicator' => 'fake_reviews',
            'detected' => false,
            'confidence' => 0,
        ];
    }
}
