<?php

declare(strict_types=1);

namespace App\Services\KYB;

use App\Models\KYBVerification;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;

final readonly class KYBService
{
    use WithAuditLogging;

    public function __construct(
        private readonly UBOAnalysisService $uboAnalysis,
        private readonly SanctionsScreeningService $sanctionsScreening,
        private readonly BusinessRiskScoringService $riskScoring,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
    ) {}

    /**
     * Start KYB verification for a business
     */
    public function startVerification(
        string $tenantId,
        int $businessGroupId,
        string $inn,
        string $correlationId = ''
    ): array {
        // Fraud check
        $this->fraudControl->check(
            userId: null,
            operationType: 'kyb_verification_start',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($tenantId, $businessGroupId, $inn, $correlationId) {
            // Create KYB verification record
            $kybVerification = KYBVerification::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'inn' => $inn,
                'verification_status' => 'in_progress',
                'risk_score' => 0,
                'risk_level' => 'medium',
                'correlation_id' => $correlationId,
            ]);

            // Step 1: Extract UBO chain
            $uboChain = $this->uboAnalysis->extractChain($inn, $kybVerification->id, $correlationId);

            // Step 2: Screen all entities for sanctions
            $sanctionsResults = $this->sanctionsScreening->screenAllEntities(
                $kybVerification->id,
                $uboChain,
                $correlationId
            );

            // Step 3: Screen for PEP (Politically Exposed Persons)
            $pepResults = $this->pepScreening->screenAllEntities(
                $kybVerification->id,
                $uboChain,
                $correlationId
            );

            // Step 4: Screen for adverse media
            $adverseMediaResults = $this->adverseMediaScreening->screenAllEntities(
                $kybVerification->id,
                $uboChain,
                $correlationId
            );

            // Step 5: Build ownership graph and detect patterns
            $linkAnalysisResults = $this->linkAnalysis->buildOwnershipGraph(
                $kybVerification->id,
                $uboChain,
                $correlationId
            );

            // Step 6: Calculate risk score
            $riskScore = $this->riskScoring->calculateRisk(
                $kybVerification->id,
                $uboChain,
                $sanctionsResults,
                $correlationId
            );

            // Update verification record
            $kybVerification->update([
                'risk_score' => $riskScore['overall_score'],
                'risk_level' => $riskScore['risk_level'],
                'ubo_chain' => $uboChain,
                'sanctions_screening' => $sanctionsResults,
                'verification_status' => $riskScore['requires_manual_review'] ? 'requires_review' : 'approved',
                'manual_review_required' => $riskScore['requires_manual_review'],
                'verified_at' => CarbonImmutable::now(),
                'expires_at' => CarbonImmutable::now()->addYear(),
            ]);

            // Auto-reject if critical risk
            if ($riskScore['risk_level'] === 'critical') {
                $kybVerification->update(['verification_status' => 'rejected']);

                $this->log->channel('security')->critical('KYB verification auto-rejected due to critical risk', [
                    'tenant_id' => $tenantId,
                    'business_group_id' => $businessGroupId,
                    'inn' => $inn,
                    'risk_score' => $riskScore['overall_score'],
                ]);
            }

            // Audit log
            $this->audit->record(
                action: 'kyb_verification_completed',
                subjectType: KYBVerification::class,
                subjectId: $kybVerification->id,
                newValues: [
                    'risk_score' => $riskScore['overall_score'],
                    'risk_level' => $riskScore['risk_level'],
                    'verification_status' => $kybVerification->verification_status,
                ],
                correlationId: $correlationId,
            );

            return [
                'kyb_verification_id' => $kybVerification->id,
                'verification_status' => $kybVerification->verification_status,
                'risk_score' => $kybVerification->risk_score,
                'risk_level' => $kybVerification->risk_level,
                'manual_review_required' => $kybVerification->manual_review_required,
            ];
        });
    }

    /**
     * Re-screen existing business (periodic re-verification)
     */
    public function reScreenBusiness(string $tenantId, string $correlationId = ''): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        $mainBusinessGroup = $tenant->businessGroups()->main()->first();

        if (! $mainBusinessGroup) {
            throw new \DomainException('Main business group not found');
        }

        return $this->startVerification(
            $tenantId,
            $mainBusinessGroup->id,
            $tenant->inn,
            $correlationId
        );
    }

    /**
     * Get KYB verification status
     */
    public function getVerificationStatus(string $tenantId): array
    {
        $kybVerification = KYBVerification::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->first();

        if (! $kybVerification) {
            return [
                'verified' => false,
                'status' => 'not_started',
            ];
        }

        return [
            'verified' => $kybVerification->verification_status === 'approved',
            'status' => $kybVerification->verification_status,
            'risk_score' => $kybVerification->risk_score,
            'risk_level' => $kybVerification->risk_level,
            'verified_at' => $kybVerification->verified_at,
            'expires_at' => $kybVerification->expires_at,
            'manual_review_required' => $kybVerification->manual_review_required,
        ];
    }
}
