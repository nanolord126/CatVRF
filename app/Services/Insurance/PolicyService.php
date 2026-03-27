<?php

declare(strict_types=1);

namespace App\Services\Insurance;

use App\Models\Insurance\InsurancePolicy;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceCompany;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PolicyService (Insurance Policy Management).
 * Implementation: Layer 4 (Business Logic).
 * Requirements: >60 lines, correlation_id, DB::transaction, audit-log.
 * Logic: Handles policy creation, signing, and state transitions.
 */
final readonly class PolicyService
{
    /**
     * Issue a formal policy with legal contract.
     */
    public function issuePolicy(
        InsuranceCompany $company,
        User $user,
        int $typeId,
        int $premiumAmount,
        int $coverageAmount,
        array $policyData,
        string $correlationId = null
    ): InsurancePolicy {
        $correlationId = $correlationId ?? (string) Str::uuid();

        // 1. Audit Log Start (Canon 2026: Logic trace)
        Log::channel('audit')->info('[PolicyService] Issuing new policy', [
            'correlation_id' => $correlationId,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'premium' => $premiumAmount,
            'coverage' => $coverageAmount,
        ]);

        try {
            // 2. Transaction Scope (Atomic Operation)
            return DB::transaction(function () use (
                $company, $user, $typeId, $premiumAmount, $coverageAmount, $policyData, $correlationId
            ) {
                // 3. Create Core Policy Record
                $policy = InsurancePolicy::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $company->tenant_id,
                    'company_id' => $company->id,
                    'type_id' => $typeId,
                    'user_id' => $user->id,
                    'policy_number' => 'POL-' . strtoupper(Str::random(12)),
                    'premium_amount' => $premiumAmount,
                    'coverage_amount' => $coverageAmount,
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(),
                    'status' => 'pending', // Pending signature and payment
                    'policy_data' => $policyData,
                    'correlation_id' => $correlationId,
                    'tags' => ['new', 'issued', 'pending-payment'],
                ]);

                // 4. Verification Step (Logical consistency)
                if ($premiumAmount <= 0) {
                    throw new Exception('[PolicyService] Premium cannot be zero or negative.');
                }

                if ($coverageAmount < ($premiumAmount * 2)) {
                    throw new Exception('[PolicyService] Coverage must be at least 2x the premium.');
                }

                // 5. Initialize Legal Contract
                $policy->contract()->create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $company->tenant_id,
                    'document_url' => 'https://storage.cdn/contracts/' . $policy->policy_number . '.pdf',
                    'correlation_id' => $correlationId,
                ]);

                // 6. Success Log Audit
                Log::channel('audit')->info('[PolicyService] Policy successfully issued', [
                    'correlation_id' => $correlationId,
                    'policy_uuid' => $policy->uuid,
                    'policy_number' => $policy->policy_number,
                ]);

                return $policy;
            });

        } catch (Exception $e) {
            // 7. Error handling (Log and bubble up)
            Log::channel('audit')->error('[PolicyService] Policy issuance failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark policy as active after payment and signing.
     */
    public function activatePolicy(int $policyId, string $correlationId = null): bool
    {
        $correlationId = $correlationId ?? (string) Str::uuid();

        return DB::transaction(function () use ($policyId, $correlationId) {
            $policy = InsurancePolicy::lockForUpdate()->findOrFail($policyId);

            if ($policy->status === 'active') {
                return true;
            }

            // check if signed contract exists
            if (!$policy->contract || !$policy->contract->signed_at) {
                Log::channel('audit')->warning('[PolicyService] Policy activation skipped: contract not signed', [
                    'correlation_id' => $correlationId,
                    'policy_id' => $policyId,
                ]);
                return false;
            }

            $policy->update([
                'status' => 'active',
                'tags' => array_merge($policy->tags ?? [], ['active', 'paid']),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('[PolicyService] Policy activated', [
                'policy_id' => $policyId,
                'correlation_id' => $correlationId,
            ]);

            return true;
        });
    }

    /**
     * Get active policies for a specific user.
     */
    public function getUserActivePolicies(int $userId): Collection
    {
        return InsurancePolicy::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->get();
    }
}
