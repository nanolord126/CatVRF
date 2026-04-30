<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tender;
use App\Models\TenderBid;
use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\DB;

final class PlatformGuaranteeService
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly WalletService $walletService,
        private readonly TenderMLRiskAssessmentService $mlAssessment
    ) {}

    /**
     * Request platform guarantee for tender
     */
    public function requestPlatformGuarantee(int $tenderId, int $businessId): array
    {
        return $this->withSpan('platform.guarantee.request', function () use ($tenderId, $businessId) {
            $tender = Tender::where('id', $tenderId)
                ->where('business_id', $businessId)
                ->firstOrFail();

            if ($tender->status !== Tender::STATUS_DRAFT) {
                throw new \InvalidArgumentException('Platform guarantee can only be requested for draft tenders');
            }

            // ML Credit Assessment
            $assessment = $this->performCreditAssessment($businessId, $tender->tenant_id, $tender->min_amount, $tender->vertical_id);
            
            // Fraud Check with vertical indicators
            $fraudCheck = $this->performFraudCheck($businessId, $tender->min_amount, $tender->vertical_id);

            // Determine eligibility
            $eligible = $assessment['eligible'] && $fraudCheck['passed'];
            
            $guaranteeFee = $tender->min_amount * (Tender::PLATFORM_GUARANTEE_FEE_PERCENT / 100);

            if ($eligible) {
                $tender->requires_platform_guarantee = true;
                $tender->guarantee_fee_amount = $guaranteeFee;
                $tender->credit_score = $assessment['score'];
                $tender->credit_risk_level = $assessment['risk_level'];
                $tender->credit_assessed_at = now();
                $tender->fraud_check_passed = $fraudCheck['passed'];
                $tender->fraud_check_notes = $fraudCheck['notes'];
                $tender->save();

                $this->logAction('tender', $tenderId, 'platform_guarantee_requested', [
                    'fee_amount' => $guaranteeFee,
                    'credit_score' => $assessment['score'],
                    'risk_level' => $assessment['risk_level'],
                    'vertical' => $assessment['vertical'],
                    'fraud_passed' => $fraudCheck['passed'],
                    'vertical_risk_indicators' => $fraudCheck['vertical_risk'],
                ], $businessId);
            }

            return [
                'eligible' => $eligible,
                'fee_amount' => $guaranteeFee,
                'credit_assessment' => $assessment,
                'fraud_check' => $fraudCheck,
            ];
        });
    }

    /**
     * Pay platform guarantee fee
     */
    public function payGuaranteeFee(int $tenderId, int $businessId): Tender
    {
        return DB::transaction(function () use ($tenderId, $businessId) {
            $tender = Tender::where('id', $tenderId)
                ->where('business_id', $businessId)
                ->firstOrFail();

            if (!$tender->requires_platform_guarantee) {
                throw new \InvalidArgumentException('This tender does not require platform guarantee');
            }

            if ($tender->guarantee_paid_at) {
                throw new \InvalidArgumentException('Guarantee fee already paid');
            }

            // Deduct fee from wallet
            $this->walletService->withdraw(
                $businessId,
                $tender->tenant_id,
                $tender->guarantee_fee_amount,
                'Platform guarantee fee for tender'
            );

            $tender->guarantee_paid_at = now();
            $tender->save();

            $this->logAction('tender', $tenderId, 'guarantee_fee_paid', [
                'amount' => $tender->guarantee_fee_amount,
            ], $businessId);

            return $tender;
        });
    }

    /**
     * Hold funds from customer (4 days before delivery)
     */
    public function holdCustomerFunds(int $tenderId, int $bidId, int $businessId): Tender
    {
        return DB::transaction(function () use ($tenderId, $bidId, $businessId) {
            $tender = Tender::findOrFail($tenderId);
            $bid = $tender->bids()->where('id', $bidId)->firstOrFail();

            if (!$tender->delivery_start_date) {
                throw new \InvalidArgumentException('Delivery start date is required for hold calculation');
            }

            $holdAmount = $bid->bid_amount;
            $holdReleaseDate = $tender->delivery_start_date->subDays(Tender::HOLD_DAYS_BEFORE_DELIVERY);

            // Hold funds from business wallet
            $this->walletService->hold(
                $businessId,
                $tender->tenant_id,
                $holdAmount,
                'Hold for tender delivery'
            );

            $tender->hold_amount = $holdAmount;
            $tender->hold_frozen_at = now();
            $tender->hold_release_date = $holdReleaseDate;
            $tender->save();

            $this->logAction('tender', $tenderId, 'funds_held', [
                'amount' => $holdAmount,
                'release_date' => $holdReleaseDate->toIso8601String(),
            ], $businessId);

            return $tender;
        });
    }

    /**
     * Release held funds
     */
    public function releaseHeldFunds(int $tenderId, int $businessId): Tender
    {
        return DB::transaction(function () use ($tenderId, $businessId) {
            $tender = Tender::where('id', $tenderId)
                ->where('business_id', $businessId)
                ->firstOrFail();

            if (!$tender->hold_amount || $tender->hold_released_at) {
                throw new \InvalidArgumentException('No funds to release or already released');
            }

            // Release funds
            $this->walletService->releaseHold(
                $businessId,
                $tender->tenant_id,
                $tender->hold_amount
            );

            $tender->hold_released_at = now();
            $tender->save();

            $this->logAction('tender', $tenderId, 'funds_released', [
                'amount' => $tender->hold_amount,
            ], $businessId);

            return $tender;
        });
    }

    /**
     * Perform ML credit assessment
     */
    private function performCreditAssessment(int $businessId, int $tenantId, float $amount, int $verticalId): array
    {
        // Use vertical-specific ML assessment
        $assessment = $this->mlAssessment->assessRisk(
            $businessId,
            $tenantId,
            $verticalId,
            $amount
        );

        return [
            'score' => $assessment['score'],
            'risk_level' => $assessment['risk_level'],
            'eligible' => $assessment['eligible'],
            'vertical' => $assessment['vertical'],
            'features' => $assessment['features'],
        ];
    }

    /**
     * Perform fraud check with vertical-specific indicators
     */
    private function performFraudCheck(int $businessId, float $amount, int $verticalId): array
    {
        // Base fraud check
        $fraudResult = $this->fraudControl->check($businessId, 'tender_guarantee', $amount);
        
        // Vertical-specific fraud indicators
        $verticalIndicators = $this->mlAssessment->checkVerticalFraudIndicators(
            $businessId,
            $verticalId,
            $amount
        );
        
        // Combine results
        $hasFraud = ($fraudResult['is_fraud'] ?? false) || $verticalIndicators['has_risk'];
        
        return [
            'passed' => !$hasFraud,
            'notes' => $fraudResult['reason'] ?? null,
            'risk_score' => $fraudResult['risk_score'] ?? 0,
            'vertical_indicators' => $verticalIndicators['indicators'],
            'vertical_risk' => $verticalIndicators['has_risk'],
        ];
    }

    /**
     * Check if all documents are signed before closing tender
     */
    public function validateDocumentsSigned(int $tenderId): array
    {
        $tender = Tender::findOrFail($tenderId);
        
        $requiredDocs = ['contract', 'upd', 'invoice', 'act'];
        $signedDocs = TenderDocument::where('tender_id', $tenderId)
            ->whereIn('type', $requiredDocs)
            ->where('status', TenderDocument::STATUS_SIGNED)
            ->count();
        
        $allSigned = $signedDocs === count($requiredDocs);
        
        if ($allSigned && !$tender->documents_signed) {
            $tender->documents_signed = true;
            $tender->documents_signed_at = now();
            $tender->save();
        }
        
        return [
            'all_signed' => $allSigned,
            'signed_count' => $signedDocs,
            'required_count' => count($requiredDocs),
            'missing_docs' => $allSigned ? [] : array_diff($requiredDocs, 
                TenderDocument::where('tender_id', $tenderId)
                    ->whereIn('type', $requiredDocs)
                    ->where('status', TenderDocument::STATUS_SIGNED)
                    ->pluck('type')
                    ->toArray()
            ),
        ];
    }
}
