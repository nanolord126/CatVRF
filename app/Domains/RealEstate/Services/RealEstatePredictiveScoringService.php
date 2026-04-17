<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Services\AI\RealEstateAIConstructorService;
use App\Domains\RealEstate\Services\RealEstateBlockchainVerificationService;
use App\Domains\RealEstate\Services\RealEstateEscrowWalletService;
use App\Domains\RealEstate\Services\RealEstateWebRTCService;
use App\Domains\RealEstate\Services\RealEstateCRMIntegrationService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\FraudMLService;
use App\Services\ML\UserBehaviorAnalyzerService;
use App\Services\ML\RecommendationService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Database\Connection;
use Illuminate\Log\LogManager;
use Carbon\Carbon;

final readonly class RealEstatePredictiveScoringService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const CREDIT_SCORE_WEIGHT = 0.40;
    private const LEGAL_SCORE_WEIGHT = 0.30;
    private const LIQUIDITY_SCORE_WEIGHT = 0.30;
    private const APPROVAL_THRESHOLD = 0.80;
    private const REVIEW_THRESHOLD = 0.60;
    private const DECLINE_THRESHOLD = 0.40;
    private const MORTGAGE_BASE_RATE = 12.0;
    private const MORTGAGE_MAX_DISCOUNT = 4.0;
    private const FLASH_DISCOUNT_THRESHOLD = 0.75;
    private const FLASH_DISCOUNT_MAX_PERCENT = 0.15;
    private const B2B_DISCOUNT_TIER_1 = 0.08;
    private const B2B_DISCOUNT_TIER_2 = 0.12;
    private const B2B_MIN_DEAL_AMOUNT = 5000000.00;

    public function __construct(
        private FraudControlService $fraudControl,
        private AuditService $audit,
        private FraudMLService $fraudML,
        private UserBehaviorAnalyzerService $behaviorAnalyzer,
        private RecommendationService $recommendation,
        private RealEstateAIConstructorService $aiConstructor,
        private RealEstateBlockchainVerificationService $blockchain,
        private RealEstateEscrowWalletService $escrowWallet,
        private RealEstateWebRTCService $webrtc,
        private RealEstateCRMIntegrationService $crm,
        private Repository $cache,
        private Connection $db,
        private LogManager $logger
    ) {}

    public function calculateDealScore(
        Property $property,
        int $userId,
        float $dealAmount,
        bool $isB2B,
        string $correlationId,
        ?string $idempotencyKey = null
    ): array {
        $this->fraudControl->check(
            $userId,
            'calculate_deal_score',
            (int) $dealAmount,
            $this->getRequestIpAddress(),
            $this->getRequestDeviceFingerprint(),
            $correlationId
        );

        $cacheKey = "scoring:{$property->id}:{$userId}:{$isB2B}:{$idempotencyKey}";
        if ($idempotencyKey !== null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return json_decode($cached, true);
            }
        }

        $result = $this->db->transaction(function () use ($property, $userId, $dealAmount, $isB2B, $correlationId) {
            $creditScore = $this->calculateCreditScore($property, $userId, $dealAmount, $correlationId);
            $legalScore = $this->calculateLegalScore($property, $userId, $correlationId);
            $liquidityScore = $this->calculateLiquidityScore($property, $correlationId);
            $mlFraudScore = $this->calculateMLFraudScore($property, $userId, $correlationId);
            $aiLiquidityScore = $this->calculateAILiquidityScore($property, $userId, $correlationId);

            $overallScore = (
                ($creditScore['score'] * self::CREDIT_SCORE_WEIGHT) +
                ($legalScore['score'] * self::LEGAL_SCORE_WEIGHT) +
                ($liquidityScore['score'] * 0.20) +
                ($mlFraudScore['score'] * 0.05) +
                ($aiLiquidityScore['score'] * 0.05)
            );

            $recommendation = $this->getRecommendation($overallScore);
            $mortgageRate = $this->estimateMortgageRate($overallScore, $isB2B);
            $flashDiscount = $this->calculateFlashDiscount($overallScore, $property, $correlationId);
            $dynamicPrice = $this->calculateDynamicPrice($property, $overallScore, $isB2B, $flashDiscount, $correlationId);
            $riskFactors = $this->identifyRiskFactors($creditScore, $legalScore, $liquidityScore, $mlFraudScore);
            $blockchainStatus = $this->getBlockchainVerificationStatus($property, $correlationId);
            $escrowEligibility = $this->calculateEscrowEligibility($overallScore, $dealAmount, $isB2B, $correlationId);
            $webrtcEnabled = $this->checkWebRTCEligibility($property, $userId, $correlationId);
            $crmSyncStatus = $this->syncWithCRM($property, $userId, $overallScore, $recommendation, $correlationId);

            $scoringResult = [
                'property_id' => $property->id,
                'property_uuid' => $property->uuid,
                'user_id' => $userId,
                'deal_amount' => $dealAmount,
                'is_b2b' => $isB2B,
                'overall_score' => round($overallScore, 4),
                'credit_score' => $creditScore,
                'legal_score' => $legalScore,
                'liquidity_score' => $liquidityScore,
                'ml_fraud_score' => $mlFraudScore,
                'ai_liquidity_score' => $aiLiquidityScore,
                'recommendation' => $recommendation,
                'mortgage_rate_estimate' => $mortgageRate,
                'flash_discount_percent' => $flashDiscount,
                'dynamic_price' => $dynamicPrice,
                'risk_factors' => $riskFactors,
                'blockchain_verified' => $blockchainStatus['verified'],
                'blockchain_details' => $blockchainStatus,
                'escrow_eligible' => $escrowEligibility['eligible'],
                'escrow_details' => $escrowEligibility,
                'webrtc_enabled' => $webrtcEnabled,
                'crm_synced' => $crmSyncStatus['synced'],
                'crm_details' => $crmSyncStatus,
                'calculated_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

            $this->audit->record(
                'predictive_scoring_calculated',
                'App\Domains\RealEstate\Models\Property',
                $property->id,
                [],
                [
                    'user_id' => $userId,
                    'overall_score' => $overallScore,
                    'recommendation' => $recommendation,
                    'is_b2b' => $isB2B,
                    'dynamic_price' => $dynamicPrice,
                ],
                $correlationId
            );

            $this->logger->channel('audit')->info('RealEstate predictive scoring completed', [
                'property_id' => $property->id,
                'user_id' => $userId,
                'overall_score' => $overallScore,
                'recommendation' => $recommendation,
                'correlation_id' => $correlationId,
            ]);

            return $scoringResult;
        });

        if ($idempotencyKey !== null) {
            $this->cache->put($cacheKey, json_encode($result), self::CACHE_TTL_SECONDS);
        }

        return $result;
    }

    public function calculateBulkScores(
        array $propertyIds,
        int $userId,
        bool $isB2B,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'calculate_bulk_scores',
            0,
            $this->getRequestIpAddress(),
            $this->getRequestDeviceFingerprint(),
            $correlationId
        );

        $properties = Property::whereIn('id', $propertyIds)->get();
        $scoringResults = [];
        $aiRecommendations = $this->aiConstructor->getBulkPropertyRecommendations($propertyIds, $userId, $correlationId);

        foreach ($properties as $property) {
            $scoringResults[$property->id] = $this->calculateDealScore(
                $property,
                $userId,
                $property->price,
                $isB2B,
                $correlationId,
                null
            );
            $scoringResults[$property->id]['ai_recommendations'] = $aiRecommendations[$property->id] ?? [];
        }

        $this->audit->record(
            'bulk_predictive_scoring_calculated',
            'App\Domains\RealEstate\Models\Property',
            null,
            [],
            [
                'property_count' => count($propertyIds),
                'user_id' => $userId,
                'is_b2b' => $isB2B,
            ],
            $correlationId
        );

        return [
            'property_scores' => $scoringResults,
            'total_properties' => count($scoringResults),
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];
    }

    public function getUserEligibility(
        int $userId,
        float $requestedAmount,
        bool $isB2B,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'check_user_eligibility',
            (int) $requestedAmount,
            $this->getRequestIpAddress(),
            $this->getRequestDeviceFingerprint(),
            $correlationId
        );

        $cacheKey = "eligibility:{$userId}:{$requestedAmount}:{$isB2B}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $userBehavior = $this->behaviorAnalyzer->classifyUser($userId);
        $userHistoryScore = $this->calculateUserHistoryScore($userId, $userBehavior);
        $priceAffordability = $this->calculatePriceAffordability($userId, $requestedAmount, $isB2B);
        $fraudRiskScore = $this->fraudML->scoreOperation($userId, 'eligibility_check', (int) $requestedAmount, $this->getRequestIpAddress(), $this->getRequestDeviceFingerprint(), $correlationId);
        $mlScore = isset($fraudRiskScore['score']) ? (float) $fraudRiskScore['score'] : 0.0;

        $eligibilityScore = ($userHistoryScore * 0.5) + ($priceAffordability * 0.3) + ((1.0 - $mlScore) * 0.2);
        $isEligible = $eligibilityScore >= self::APPROVAL_THRESHOLD;
        $maxEligibleAmount = $requestedAmount / max(0.01, $eligibilityScore);
        $b2bTier = $isB2B ? $this->calculateB2BTier($requestedAmount) : null;
        $creditLimit = $isB2B ? $this->calculateB2BCreditLimit($userId, $b2bTier) : 0.0;

        $eligibilityResult = [
            'user_id' => $userId,
            'requested_amount' => $requestedAmount,
            'is_b2b' => $isB2B,
            'eligibility_score' => round($eligibilityScore, 4),
            'is_eligible' => $isEligible,
            'max_eligible_amount' => $maxEligibleAmount,
            'user_history_score' => $userHistoryScore,
            'price_affordability_score' => $priceAffordability,
            'fraud_risk_score' => $mlScore,
            'user_behavior_type' => $userBehavior,
            'b2b_tier' => $b2bTier,
            'credit_limit' => $creditLimit,
            'calculated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        $this->cache->put($cacheKey, json_encode($eligibilityResult), self::CACHE_TTL_SECONDS);

        $this->audit->record(
            'user_eligibility_checked',
            'App\Models\User',
            $userId,
            [],
            [
                'requested_amount' => $requestedAmount,
                'is_eligible' => $isEligible,
                'eligibility_score' => $eligibilityScore,
            ],
            $correlationId
        );

        return $eligibilityResult;
    }

    private function calculateCreditScore(Property $property, int $userId, float $dealAmount, string $correlationId): array
    {
        $userBehavior = $this->behaviorAnalyzer->classifyUser($userId);
        $userHistoryScore = $this->calculateUserHistoryScore($userId, $userBehavior);
        $priceAffordability = $this->calculatePriceAffordability($userId, $dealAmount, false);
        $incomeToDebtRatio = $this->estimateIncomeToDebtRatio($userId, $dealAmount);
        $paymentHistoryScore = $this->calculatePaymentHistoryScore($userId, $correlationId);
        $dealToIncomeRatio = $this->calculateDealToIncomeRatio($userId, $dealAmount);

        $creditScore = ($userHistoryScore * 0.30) + ($priceAffordability * 0.25) + ($incomeToDebtRatio * 0.20) + ($paymentHistoryScore * 0.15) + ($dealToIncomeRatio * 0.10);

        $factors = [
            'user_history_score' => $userHistoryScore,
            'price_affordability_score' => $priceAffordability,
            'income_to_debt_ratio' => $incomeToDebtRatio,
            'payment_history_score' => $paymentHistoryScore,
            'deal_to_income_ratio' => $dealToIncomeRatio,
        ];

        return [
            'score' => round($creditScore, 4),
            'factors' => $factors,
            'rating' => $this->getCreditRating($creditScore),
        ];
    }

    private function calculateLegalScore(Property $property, int $userId, string $correlationId): array
    {
        $metadata = $property->metadata ?? [];
        $titleClear = isset($metadata['title_clear']) && $metadata['title_clear'] === true ? 1.0 : 0.5;
        $noLiens = isset($metadata['no_liens']) && $metadata['no_liens'] === true ? 1.0 : 0.5;
        $zoningCompliant = isset($metadata['zoning_compliant']) && $metadata['zoning_compliant'] === true ? 1.0 : 0.5;
        $buildingPermitValid = isset($metadata['building_permit_valid']) && $metadata['building_permit_valid'] === true ? 1.0 : 0.5;
        $taxClearance = isset($metadata['tax_clearance']) && $metadata['tax_clearance'] === true ? 1.0 : 0.5;
        $ownershipVerified = isset($metadata['ownership_verified']) && $metadata['ownership_verified'] === true ? 1.0 : 0.5;
        $documentsComplete = isset($metadata['documents_complete']) && $metadata['documents_complete'] === true ? 1.0 : 0.5;

        $legalScore = ($titleClear * 0.25) + ($noLiens * 0.20) + ($zoningCompliant * 0.15) + ($buildingPermitValid * 0.15) + ($taxClearance * 0.10) + ($ownershipVerified * 0.10) + ($documentsComplete * 0.05);

        $factors = [
            'title_clear' => $titleClear,
            'no_liens' => $noLiens,
            'zoning_compliant' => $zoningCompliant,
            'building_permit_valid' => $buildingPermitValid,
            'tax_clearance' => $taxClearance,
            'ownership_verified' => $ownershipVerified,
            'documents_complete' => $documentsComplete,
        ];

        return [
            'score' => round($legalScore, 4),
            'factors' => $factors,
            'rating' => $this->getLegalRating($legalScore),
        ];
    }

    private function calculateLiquidityScore(Property $property, string $correlationId): array
    {
        $daysOnMarket = $this->getDaysOnMarket($property);
        $priceCompetitiveness = $this->getPriceCompetitiveness($property);
        $locationScore = $this->getLocationScore($property);
        $marketTrend = $this->getMarketTrend($property);
        $seasonalDemand = $this->getSeasonalDemandScore($property);
        $neighborhoodGrowth = $this->getNeighborhoodGrowthScore($property);

        $liquidityScore = ($daysOnMarket * 0.25) + ($priceCompetitiveness * 0.25) + ($locationScore * 0.20) + ($marketTrend * 0.15) + ($seasonalDemand * 0.10) + ($neighborhoodGrowth * 0.05);

        $factors = [
            'days_on_market_score' => $daysOnMarket,
            'price_competitiveness' => $priceCompetitiveness,
            'location_score' => $locationScore,
            'market_trend' => $marketTrend,
            'seasonal_demand' => $seasonalDemand,
            'neighborhood_growth' => $neighborhoodGrowth,
        ];

        return [
            'score' => round($liquidityScore, 4),
            'factors' => $factors,
            'rating' => $this->getLiquidityRating($liquidityScore),
        ];
    }

    private function getRecommendation(float $overallScore): string
    {
        if ($overallScore >= self::APPROVAL_THRESHOLD) {
            return 'approved';
        } elseif ($overallScore >= self::REVIEW_THRESHOLD) {
            return 'review';
        } elseif ($overallScore >= self::DECLINE_THRESHOLD) {
            return 'manual_review';
        } else {
            return 'declined';
        }
    }

    private function estimateMortgageRate(float $overallScore, bool $isB2B): float
    {
        $discount = ($overallScore - 0.5) * self::MORTGAGE_MAX_DISCOUNT;
        $discount = max(0, min(self::MORTGAGE_MAX_DISCOUNT, $discount));
        $b2bDiscount = $isB2B ? 1.5 : 0.0;

        return round(self::MORTGAGE_BASE_RATE - $discount - $b2bDiscount, 2);
    }

    private function identifyRiskFactors(array $creditScore, array $legalScore, array $liquidityScore, array $mlFraudScore): array
    {
        $riskFactors = [];

        if ($creditScore['score'] < self::REVIEW_THRESHOLD) {
            $riskFactors[] = 'low_credit_score';
        }

        if ($legalScore['score'] < self::REVIEW_THRESHOLD) {
            $riskFactors[] = 'legal_document_issues';
        }

        if ($liquidityScore['score'] < self::REVIEW_THRESHOLD) {
            $riskFactors[] = 'low_liquidity_risk';
        }

        if ($mlFraudScore['score'] > 0.6) {
            $riskFactors[] = 'high_fraud_risk';
        }

        if (isset($creditScore['factors']['price_affordability_score']) && $creditScore['factors']['price_affordability_score'] < 0.5) {
            $riskFactors[] = 'affordability_concern';
        }

        if (isset($legalScore['factors']['title_clear']) && !$legalScore['factors']['title_clear']) {
            $riskFactors[] = 'title_unclear';
        }

        if (isset($legalScore['factors']['no_liens']) && !$legalScore['factors']['no_liens']) {
            $riskFactors[] = 'property_liens_detected';
        }

        if (isset($liquidityScore['factors']['days_on_market_score']) && $liquidityScore['factors']['days_on_market_score'] < 0.5) {
            $riskFactors[] = 'extended_days_on_market';
        }

        return $riskFactors;
    }

    private function getCreditRating(float $score): string
    {
        if ($score >= 0.9) return 'excellent';
        if ($score >= 0.8) return 'good';
        if ($score >= 0.7) return 'fair';
        if ($score >= 0.6) return 'poor';
        return 'very_poor';
    }

    private function getLegalRating(float $score): string
    {
        if ($score >= 0.9) return 'compliant';
        if ($score >= 0.7) return 'minor_issues';
        if ($score >= 0.5) return 'moderate_issues';
        return 'significant_issues';
    }

    private function getLiquidityRating(float $score): string
    {
        if ($score >= 0.8) return 'high_liquidity';
        if ($score >= 0.6) return 'moderate_liquidity';
        return 'low_liquidity';
    }

    private function calculateUserHistoryScore(int $userId, string $userBehavior): float
    {
        $completedDeals = $this->db->table('real_estate_transactions')
            ->where('buyer_id', $userId)
            ->where('status', 'completed')
            ->count();

        $cancelledDeals = $this->db->table('real_estate_transactions')
            ->where('buyer_id', $userId)
            ->where('status', 'cancelled')
            ->count();

        $totalDeals = $completedDeals + $cancelledDeals;

        if ($totalDeals === 0) {
            return $userBehavior === 'returning' ? 0.7 : 0.5;
        }

        $completionRate = $completedDeals / max(1, $totalDeals);
        $historyScore = $completionRate * 0.8 + min($completedDeals / 10, 1.0) * 0.2;

        return min($historyScore, 1.0);
    }

    private function calculatePriceAffordability(int $userId, float $amount, bool $isB2B): float
    {
        $userIncome = $this->getUserEstimatedIncome($userId);
        
        if ($userIncome === 0.0) {
            return $isB2B ? 0.6 : 0.5;
        }

        $annualDealAmount = $amount * 12;
        $affordabilityRatio = $userIncome / max(1, $annualDealAmount);
        $affordabilityScore = min($affordabilityRatio, 1.0);

        return $isB2B ? min($affordabilityScore * 1.2, 1.0) : $affordabilityScore;
    }

    private function estimateIncomeToDebtRatio(int $userId, float $dealAmount): float
    {
        $userIncome = $this->getUserEstimatedIncome($userId);
        $existingDebt = $this->getUserExistingDebt($userId);
        
        if ($userIncome === 0.0) {
            return 0.5;
        }

        $totalDebt = $existingDebt + ($dealAmount * 0.05);
        $debtToIncomeRatio = $totalDebt / $userIncome;
        $score = max(0.0, 1.0 - $debtToIncomeRatio);

        return $score;
    }

    private function getDaysOnMarket(Property $property): float
    {
        $createdDate = Carbon::parse($property->created_at);
        $daysOnMarket = now()->diffInDays($createdDate);

        if ($daysOnMarket < 30) {
            return 1.0;
        }
        if ($daysOnMarket < 60) {
            return 0.9;
        }
        if ($daysOnMarket < 90) {
            return 0.8;
        }
        if ($daysOnMarket < 120) {
            return 0.6;
        }
        if ($daysOnMarket < 180) {
            return 0.4;
        }
        return 0.2;
    }

    private function getPriceCompetitiveness(Property $property): float
    {
        $similarProperties = Property::where('type', $property->type)
            ->where('area_sqm', '>=', $property->area_sqm * 0.9)
            ->where('area_sqm', '<=', $property->area_sqm * 1.1)
            ->where('status', 'available')
            ->limit(10)
            ->get();

        if ($similarProperties->isEmpty()) {
            return 0.7;
        }

        $avgPrice = $similarProperties->avg('price') ?? $property->price;
        $pricePosition = ($property->price - $avgPrice) / max(1, $avgPrice);

        if ($pricePosition < -0.15) {
            return 1.0;
        }
        if ($pricePosition < -0.1) {
            return 0.95;
        }
        if ($pricePosition < -0.05) {
            return 0.85;
        }
        if ($pricePosition < 0) {
            return 0.75;
        }
        if ($pricePosition < 0.05) {
            return 0.65;
        }
        if ($pricePosition < 0.1) {
            return 0.55;
        }
        return 0.4;
    }

    private function getLocationScore(Property $property): float
    {
        $locationMetadata = $property->metadata ?? [];
        $metroDistance = $locationMetadata['metro_distance_meters'] ?? 10000;
        $schoolDistance = $locationMetadata['school_distance_meters'] ?? 5000;
        $parkDistance = $locationMetadata['park_distance_meters'] ?? 3000;
        $infrastructureScore = $locationMetadata['infrastructure_score'] ?? 0.7;
        $crimeRate = $locationMetadata['crime_rate'] ?? 0.3;

        $metroScore = max(0.0, 1.0 - ($metroDistance / 5000));
        $schoolScore = max(0.0, 1.0 - ($schoolDistance / 3000));
        $parkScore = max(0.0, 1.0 - ($parkDistance / 2000));
        $safetyScore = 1.0 - $crimeRate;

        $locationScore = ($metroScore * 0.25) + ($schoolScore * 0.20) + ($parkScore * 0.15) + ($infrastructureScore * 0.25) + ($safetyScore * 0.15);

        return round($locationScore, 4);
    }

    private function getMarketTrend(Property $property): float
    {
        $propertyType = $property->type;
        $thirtyDaysAgo = now()->subDays(30);

        $recentSoldCount = Property::where('type', $propertyType)
            ->where('status', 'sold')
            ->where('sold_at', '>=', $thirtyDaysAgo)
            ->count();

        $recentListedCount = Property::where('type', $propertyType)
            ->where('status', 'available')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        if ($recentListedCount === 0) {
            return 0.7;
        }

        $sellThroughRate = $recentSoldCount / $recentListedCount;
        $trendScore = min($sellThroughRate * 2, 1.0);

        return round($trendScore, 4);
    }

    private function calculateMLFraudScore(Property $property, int $userId, string $correlationId): array
    {
        $fraudResult = $this->fraudML->scoreOperation(
            userId: $userId,
            operationType: 'real_estate_deal',
            amount: (int) $property->price,
            ipAddress: $this->getRequestIpAddress(),
            deviceFingerprint: $this->getRequestDeviceFingerprint(),
            correlationId: $correlationId
        );

        $mlScore = isset($fraudResult['score']) ? (float) $fraudResult['score'] : 0.0;
        $trustScore = 1.0 - $mlScore;

        return [
            'score' => $trustScore,
            'fraud_risk' => $mlScore,
            'factors' => [
                'ml_model_score' => $mlScore,
                'property_history' => $this->getPropertyFraudHistory($property->id),
                'owner_history' => $this->getOwnerFraudHistory($property->owner_id ?? 0),
            ],
        ];
    }

    private function calculateAILiquidityScore(Property $property, int $userId, string $correlationId): array
    {
        $aiAnalysis = $this->aiConstructor->analyzePropertyLiquidity($property, $userId, $correlationId);

        return [
            'score' => isset($aiAnalysis['liquidity_score']) ? (float) $aiAnalysis['liquidity_score'] : 0.7,
            'factors' => [
                'ai_demand_prediction' => $aiAnalysis['demand_prediction'] ?? 0.7,
                'ai_price_optimization' => $aiAnalysis['price_optimization'] ?? 0.7,
                'ai_market_position' => $aiAnalysis['market_position'] ?? 0.7,
            ],
        ];
    }

    private function calculateFlashDiscount(float $overallScore, Property $property, string $correlationId): float
    {
        if ($overallScore >= self::APPROVAL_THRESHOLD) {
            return 0.0;
        }

        if ($overallScore >= self::FLASH_DISCOUNT_THRESHOLD) {
            return 0.0;
        }

        $discountFactor = (self::FLASH_DISCOUNT_THRESHOLD - $overallScore) / self::FLASH_DISCOUNT_THRESHOLD;
        $flashDiscount = $discountFactor * self::FLASH_DISCOUNT_MAX_PERCENT;

        $daysOnMarket = $this->getDaysOnMarket($property);
        if ($daysOnMarket < 0.6) {
            $flashDiscount *= 0.5;
        }

        return round($flashDiscount, 4);
    }

    private function calculateDynamicPrice(Property $property, float $overallScore, bool $isB2B, float $flashDiscount, string $correlationId): array
    {
        $basePrice = $property->price;
        $b2bDiscount = 0.0;

        if ($isB2B && $basePrice >= self::B2B_MIN_DEAL_AMOUNT) {
            $b2bDiscount = $basePrice * self::B2B_DISCOUNT_TIER_1;
            if ($basePrice >= 10000000.0) {
                $b2bDiscount = $basePrice * self::B2B_DISCOUNT_TIER_2;
            }
        }

        $flashDiscountAmount = $basePrice * $flashDiscount;
        $totalDiscount = $b2bDiscount + $flashDiscountAmount;
        $dynamicPrice = max($basePrice - $totalDiscount, $basePrice * 0.5);

        return [
            'base_price' => $basePrice,
            'b2b_discount' => $b2bDiscount,
            'flash_discount' => $flashDiscountAmount,
            'total_discount' => $totalDiscount,
            'dynamic_price' => $dynamicPrice,
            'discount_percent' => round(($totalDiscount / $basePrice) * 100, 2),
        ];
    }

    private function getBlockchainVerificationStatus(Property $property, string $correlationId): array
    {
        $metadata = $property->metadata ?? [];
        $blockchainVerified = $metadata['blockchain_verified'] ?? false;
        $smartContractAddress = $metadata['smart_contract_address'] ?? null;

        return [
            'verified' => $blockchainVerified,
            'smart_contract_address' => $smartContractAddress,
            'verification_date' => $metadata['blockchain_verification_date'] ?? null,
            'documents_verified' => $metadata['documents_verified'] ?? false,
        ];
    }

    private function calculateEscrowEligibility(float $overallScore, float $dealAmount, bool $isB2B, string $correlationId): array
    {
        $eligible = $overallScore >= self::REVIEW_THRESHOLD;
        $escrowFee = $dealAmount * ($isB2B ? 0.01 : 0.015);
        $maxHoldDuration = $isB2B ? 45 : 30;

        return [
            'eligible' => $eligible,
            'escrow_fee' => $escrowFee,
            'max_hold_duration_days' => $maxHoldDuration,
            'split_payment_available' => $isB2B,
        ];
    }

    private function checkWebRTCEligibility(Property $property, int $userId, string $correlationId): bool
    {
        $metadata = $property->metadata ?? [];
        $virtualTourEnabled = $metadata['virtual_tour_enabled'] ?? false;
        $hasAgent = $property->agent_id !== null;

        return $virtualTourEnabled && $hasAgent;
    }

    private function syncWithCRM(Property $property, int $userId, float $overallScore, string $recommendation, string $correlationId): array
    {
        $crmData = [
            'property_id' => $property->id,
            'user_id' => $userId,
            'scoring_result' => $overallScore,
            'recommendation' => $recommendation,
            'synced_at' => now()->toIso8601String(),
        ];

        $syncResult = $this->crm->syncPropertyScoring($crmData, $correlationId);

        return [
            'synced' => $syncResult['success'] ?? true,
            'crm_lead_id' => $syncResult['lead_id'] ?? null,
            'sync_timestamp' => $crmData['synced_at'],
        ];
    }

    private function calculatePaymentHistoryScore(int $userId, string $correlationId): float
    {
        $completedPayments = $this->db->table('balance_transactions')
            ->where('user_id', $userId)
            ->where('type', 'deposit')
            ->count();

        $failedPayments = $this->db->table('payment_transactions')
            ->where('user_id', $userId)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(90))
            ->count();

        $totalPayments = $completedPayments + $failedPayments;

        if ($totalPayments === 0) {
            return 0.7;
        }

        $successRate = $completedPayments / max(1, $totalPayments);
        return $successRate;
    }

    private function calculateDealToIncomeRatio(int $userId, float $dealAmount): float
    {
        $userIncome = $this->getUserEstimatedIncome($userId);
        
        if ($userIncome === 0.0) {
            return 0.5;
        }

        $ratio = $dealAmount / $userIncome;
        $score = max(0.0, 1.0 - ($ratio / 5));

        return $score;
    }

    private function getSeasonalDemandScore(Property $property): float
    {
        $currentMonth = now()->month;
        $seasonalFactors = [
            1 => 0.6, 2 => 0.65, 3 => 0.75, 4 => 0.85,
            5 => 0.9, 6 => 0.85, 7 => 0.8, 8 => 0.75,
            9 => 0.8, 10 => 0.85, 11 => 0.9, 12 => 0.7,
        ];

        return $seasonalFactors[$currentMonth] ?? 0.75;
    }

    private function getNeighborhoodGrowthScore(Property $property): float
    {
        $metadata = $property->metadata ?? [];
        $neighborhoodGrowthRate = $metadata['neighborhood_growth_rate'] ?? 0.05;
        $newDevelopments = $metadata['new_developments_count'] ?? 0;

        $growthScore = min(0.5 + ($neighborhoodGrowthRate * 5), 1.0);
        $developmentBonus = min($newDevelopments / 10, 0.2);

        return min($growthScore + $developmentBonus, 1.0);
    }

    private function getUserEstimatedIncome(int $userId): float
    {
        $estimatedIncome = $this->db->table('user_profiles')
            ->where('user_id', $userId)
            ->value('estimated_income') ?? 0.0;

        return (float) $estimatedIncome;
    }

    private function getUserExistingDebt(int $userId): float
    {
        $existingDebt = $this->db->table('user_profiles')
            ->where('user_id', $userId)
            ->value('existing_debt') ?? 0.0;

        return (float) $existingDebt;
    }

    private function getPropertyFraudHistory(int $propertyId): float
    {
        $fraudReports = $this->db->table('fraud_attempts')
            ->where('subject_type', 'App\\Domains\\RealEstate\\Models\\Property')
            ->where('subject_id', $propertyId)
            ->where('created_at', '>=', now()->subDays(365))
            ->count();

        return max(0.0, 1.0 - ($fraudReports / 10));
    }

    private function getOwnerFraudHistory(int $ownerId): float
    {
        if ($ownerId === 0) {
            return 0.8;
        }

        $fraudReports = $this->db->table('fraud_attempts')
            ->where('user_id', $ownerId)
            ->where('created_at', '>=', now()->subDays(365))
            ->count();

        return max(0.0, 1.0 - ($fraudReports / 5));
    }

    private function calculateB2BTier(float $dealAmount): ?int
    {
        if ($dealAmount < self::B2B_MIN_DEAL_AMOUNT) {
            return null;
        }

        if ($dealAmount < 10000000.0) {
            return 1;
        }

        if ($dealAmount < 50000000.0) {
            return 2;
        }

        return 3;
    }

    private function calculateB2BCreditLimit(int $userId, ?int $tier): float
    {
        if ($tier === null) {
            return 0.0;
        }

        $baseLimits = [1 => 10000000.0, 2 => 50000000.0, 3 => 200000000.0];
        $baseLimit = $baseLimits[$tier] ?? 0.0;

        $userHistory = $this->db->table('real_estate_transactions')
            ->where('buyer_id', $userId)
            ->where('is_b2b', true)
            ->where('status', 'completed')
            ->count();

        $historyMultiplier = 1.0 + min($userHistory / 10, 1.0);

        return $baseLimit * $historyMultiplier;
    }

    private function getRequestIpAddress(): ?string
    {
        return request()->ip();
    }

    private function getRequestDeviceFingerprint(): ?string
    {
        return request()->header('X-Device-Fingerprint');
    }
}
