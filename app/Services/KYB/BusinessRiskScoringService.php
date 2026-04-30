<?php

declare(strict_types=1);

namespace App\Services\KYB;

use Psr\Log\LoggerInterface;

use App\Models\BusinessRiskScore;
use Illuminate\Log\LogManager;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class BusinessRiskScoringService
{
    use WithAuditLogging;

    private const CRITICAL_THRESHOLD = 80;

    private const HIGH_THRESHOLD = 60;

    private const MEDIUM_THRESHOLD = 40;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Calculate overall risk score
     */
    public function calculateRisk(
        int $kybVerificationId,
        array $uboChain,
        array $sanctionsResults,
        string $correlationId = ''
    ): array {
        // 1. Sanctions risk score
        $sanctionsScore = $this->calculateSanctionsRisk($sanctionsResults);

        // 2. PEP risk score
        $pepScore = $this->calculatePEPRisk($uboChain);

        // 3. Adverse media risk score
        $adverseMediaScore = $this->calculateAdverseMediaRisk($kybVerificationId);

        // 4. Financial risk score (placeholder - from Kontur/Spark)
        $financialScore = $this->calculateFinancialRisk($kybVerificationId);

        // 5. Operational risk score
        $operationalScore = $this->calculateOperationalRisk($uboChain);

        // 6. Geographic risk score
        $geographicScore = $this->calculateGeographicRisk($uboChain);

        // Calculate weighted overall score
        $overallScore =
            $sanctionsScore * 0.35 +        // 35% weight
            $pepScore * 0.15 +              // 15% weight
            $adverseMediaScore * 0.15 +     // 15% weight
            $financialScore * 0.15 +        // 15% weight
            $operationalScore * 0.10 +      // 10% weight
            $geographicScore * 0.10;        // 10% weight

        $overallScore = (int) round($overallScore);
        $riskLevel = $this->determineRiskLevel($overallScore);
        $requiresManualReview = $this->requiresManualReview($overallScore, $sanctionsScore, $pepScore);

        // Store risk score
        BusinessRiskScore::create([
            'kyb_verification_id' => $kybVerificationId,
            'overall_score' => $overallScore,
            'risk_level' => $riskLevel,
            'sanctions_risk_score' => $sanctionsScore,
            'pep_risk_score' => $pepScore,
            'adverse_media_risk_score' => $adverseMediaScore,
            'financial_risk_score' => $financialScore,
            'operational_risk_score' => $operationalScore,
            'geographic_risk_score' => $geographicScore,
            'risk_factors' => [
                'sanctions_matches' => $this->countSanctionsMatches($sanctionsResults),
                'pep_entities' => $this->countPEPEntities($uboChain),
            ],
        ]);

        $this->log->$this->logger->info('Business risk score calculated', [
            'kyb_verification_id' => $kybVerificationId,
            'overall_score' => $overallScore,
            'risk_level' => $riskLevel,
            'correlation_id' => $correlationId,
        ]);

        return [
            'overall_score' => $overallScore,
            'risk_level' => $riskLevel,
            'requires_manual_review' => $requiresManualReview,
        ];
    }

    private function calculateSanctionsRisk(array $sanctionsResults): int
    {
        $matchCount = 0;
        foreach ($sanctionsResults as $result) {
            if ($result['screening_status'] === 'match') {
                $matchCount++;
            }
        }

        if ($matchCount > 0) {
            return 100; // Any match = critical
        }

        return 0;
    }

    private function calculatePEPRisk(array $uboChain): int
    {
        // Placeholder - in production, check PEP records
        return 0;
    }

    private function calculateAdverseMediaRisk(int $kybVerificationId): int
    {
        // Placeholder - in production, check adverse media alerts
        return 0;
    }

    private function calculateFinancialRisk(int $kybVerificationId): int
    {
        // Placeholder - in production, get financial data from Kontur/Spark
        return 10; // Low default risk
    }

    private function calculateOperationalRisk(array $uboChain): int
    {
        // Placeholder - check complexity of ownership structure
        $complexity = count($uboChain);

        return min($complexity * 5, 50); // More complex = higher risk, max 50
    }

    private function calculateGeographicRisk(array $uboChain): int
    {
        // Placeholder - in production, check geographic risk
        return 0;
    }

    private function determineRiskLevel(int $score): string
    {
        return match (true) {
            $score >= self::CRITICAL_THRESHOLD => 'critical',
            $score >= self::HIGH_THRESHOLD => 'high',
            $score >= self::MEDIUM_THRESHOLD => 'medium',
            default => 'low',
        };
    }

    private function requiresManualReview(int $overallScore, int $sanctionsScore, int $pepScore): bool
    {
        // Critical or high risk always requires review
        if ($overallScore >= self::HIGH_THRESHOLD) {
            return true;
        }

        // Medium risk with PEP requires review
        if ($overallScore >= self::MEDIUM_THRESHOLD && $pepScore > 0) {
            return true;
        }

        return false;
    }

    private function countSanctionsMatches(array $sanctionsResults): int
    {
        return count(array_filter($sanctionsResults, fn ($r) => $r['screening_status'] === 'match'));
    }

    private function countPEPEntities(array $uboChain): int
    {
        return 0; // Placeholder
    }
}
