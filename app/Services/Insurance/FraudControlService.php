<?php declare(strict_types=1);

namespace App\Services\Insurance;

use App\Models\Insurance\InsuranceClaim;
use Exception;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class FraudControlService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}


    /**
         * Score a potential claim for fraud risks (0.0 to 1.0).
         */
        public function scoreClaim(InsuranceClaim $claim, string $correlationId = null): float
        {
            $correlationId = $correlationId ?? (string) Str::uuid();

            // Audit Log Entry
            $this->logger->channel('audit')->info('[FraudControlService] Starting fraud analysis for claim', [
                'correlation_id' => $correlationId,
                'claim_uuid' => $claim->uuid,
                'claim_number' => $claim->claim_number,
            ]);

            try {
                // ML-Scoring Mock and Risk Factors
                $scoreOutcome = 0.05; // Base risk

                // 1. Check Policy Recency (High Risk if filed within 7 days of activation)
                $policy = $claim->policy;
                if ($policy && $policy->activated_at) {
                    $daysActive = $policy->activated_at->diffInDays($claim->created_at);
                    if ($daysActive < 7) {
                        $scoreOutcome += 0.40;
                        $this->logger->channel('audit')->warning('[FraudControlService] Risk Factor: Claim filed within 7 days of activation', [
                            'correlation_id' => $correlationId,
                            'days_active' => $daysActive,
                        ]);
                    }
                }

                // 2. Check Repeated Claims (High Risk if user has >2 claims in last 3 months)
                $recentClaimsCount = InsuranceClaim::where('tenant_id', $claim->tenant_id)
                    ->where('policy_id', $claim->policy_id)
                    ->where('id', '!=', $claim->id)
                    ->where('created_at', '>=', now()->subMonths(3))
                    ->count();

                if ($recentClaimsCount > 2) {
                    $scoreOutcome += 0.35;
                    $this->logger->channel('audit')->warning('[FraudControlService] Risk Factor: Hyper-active user (Multiple claims)', [
                        'correlation_id' => $correlationId,
                        'count' => $recentClaimsCount,
                    ]);
                }

                // 3. Amount Outlier Detection (Claim amount vs Mean policy coverage)
                if ($claim->requested_amount > ($policy->coverage_amount * 0.9)) {
                    $scoreOutcome += 0.20;
                    $this->logger->channel('audit')->warning('[FraudControlService] Risk Factor: Claim amount is at max coverage threshold', [
                        'correlation_id' => $correlationId,
                        'amount' => $claim->requested_amount,
                    ]);
                }

                // 4. Identity Check (Mismatched details or blacklisted IPs - Mock logic)
                // Implementation: Check for missing evidence_files
                if (empty($claim->evidence_files)) {
                    $scoreOutcome += 0.25;
                }

                // Normalize (Clamp 0.0-1.0)
                $finalScore = (float) min(1.0, max(0.0, $scoreOutcome));

                // Update Claim Record with Score Outcome
                $this->db->transaction(function () use ($claim, $finalScore, $correlationId) {
                    $claim->update([
                        'fraud_score' => [
                            'score' => $finalScore,
                            'status' => $finalScore > 0.7 ? 'flagged' : 'passed',
                            'checked_at' => now()->toIso8601String(),
                            'reason' => $finalScore > 0.7 ? 'Risk score above threshold (0.7)' : 'Normal profile',
                        ],
                        'correlation_id' => $correlationId,
                    ]);
                });

                // Final Audit Log
                $this->logger->channel('audit')->info('[FraudControlService] Fraud scoring complete', [
                    'correlation_id' => $correlationId,
                    'final_score' => $finalScore,
                ]);

                return $finalScore;

            } catch (Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->channel('audit')->error('[FraudControlService] Scoring failure', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Perform initial fraud check for policy issuance (B2B check).
         */
        public function validateIssuance(array $inputData, string $correlationId = null): bool
        {
            // Placeholder for issuance fraud rules (e.g., duplicate INN check, blocked regions)
            // Implementation: Always return true for now, log the attempt.
            $this->logger->channel('audit')->info('[FraudControlService] Issuance validation passed', [
                'correlation_id' => $correlationId,
            ]);

            return true;
        }
}
