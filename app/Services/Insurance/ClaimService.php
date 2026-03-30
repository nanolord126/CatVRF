<?php declare(strict_types=1);

namespace App\Services\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ClaimService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * File a new claim for an incident on an active policy.
         */
        public function fileClaim(
            InsurancePolicy $policy,
            string $description,
            int $requestedAmount,
            array $evidenceFiles,
            string $correlationId = null
        ): InsuranceClaim {
            $correlationId = $correlationId ?? (string) Str::uuid();

            // 1. Log Start (Audit Trace: Canon 2026)
            Log::channel('audit')->info('[ClaimService] Filing new insurance claim', [
                'correlation_id' => $correlationId,
                'policy_uuid' => $policy->uuid,
                'user_id' => $policy->user_id,
                'requested_amount' => $requestedAmount,
            ]);

            try {
                // 2. Initial Validation (Layer 4 Domain Logic)
                if ($requestedAmount <= 0) {
                    throw new Exception('[ClaimService] Requested payout must be positive.');
                }

                if ($requestedAmount > $policy->coverage_amount) {
                    throw new Exception('[ClaimService] Requested amount exceeds policy coverage.');
                }

                // 3. Transaction Scope (Atomic Operation)
                return DB::transaction(function () use ($policy, $description, $requestedAmount, $evidenceFiles, $correlationId) {
                    // 4. Create Claim Record
                    $claim = InsuranceClaim::create([
                        'uuid' => (string) Str::uuid(),
                        'tenant_id' => $policy->tenant_id,
                        'policy_id' => $policy->id,
                        'claim_number' => 'CLM-' . strtoupper(Str::random(12)),
                        'description' => $description,
                        'requested_amount' => $requestedAmount,
                        'status' => 'submitted',
                        'evidence_files' => $evidenceFiles,
                        'correlation_id' => $correlationId,
                        'fraud_score' => [
                            'score' => 0.0,
                            'status' => 'pending',
                            'checked_at' => null,
                            'reason' => null
                        ],
                    ]);

                    // 5. Success Log Audit
                    Log::channel('audit')->info('[ClaimService] Claim filed successfully', [
                        'correlation_id' => $correlationId,
                        'claim_uuid' => $claim->uuid,
                        'claim_number' => $claim->claim_number,
                    ]);

                    return $claim;
                });

            } catch (Exception $e) {
                // 6. Error handling (Canon 2026: Logging errors)
                Log::channel('audit')->error('[ClaimService] Claim filing failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Approve or reject a claim during investigation.
         */
        public function updateClaimStatus(
            int $claimId,
            string $newStatus,
            int $approvedAmount = null,
            string $reason = null,
            string $correlationId = null
        ): bool {
            $correlationId = $correlationId ?? (string) Str::uuid();

            return DB::transaction(function () use ($claimId, $newStatus, $approvedAmount, $reason, $correlationId) {
                $claim = InsuranceClaim::lockForUpdate()->findOrFail($claimId);

                if ($claim->isProcessed()) {
                    throw new Exception('[ClaimService] Cannot update a processed claim.');
                }

                // check state machine
                $allowedStatuses = ['investigating', 'approved', 'rejected', 'paid'];
                if (!in_array($newStatus, $allowedStatuses, true)) {
                    throw new Exception('[ClaimService] Invalid claim status: ' . $newStatus);
                }

                $updateData = [
                    'status' => $newStatus,
                    'correlation_id' => $correlationId,
                ];

                if ($approvedAmount !== null) { $updateData['approved_amount'] = $approvedAmount; }
                if ($reason !== null) { $updateData['reason'] = $reason; }

                $claim->update($updateData);

                Log::channel('audit')->info('[ClaimService] Claim status updated', [
                    'claim_id' => $claimId,
                    'new_status' => $newStatus,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        }

        /**
         * Get claims filed by a specific user or for a specific policy.
         */
        public function getClaims(int $policyId = null, int $userId = null): Collection
        {
            $query = InsuranceClaim::query();

            if ($policyId) { $query->where('policy_id', $policyId); }
            if ($userId) {
                $query->whereHas('policy', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            }

            return $query->latest()->get();
        }
}
