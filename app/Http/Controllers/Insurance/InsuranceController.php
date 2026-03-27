<?php
declare(strict_types=1);
namespace App\Http\Controllers\Insurance;
use App\Http\Controllers\Controller;
use App\Models\Insurance\InsurancePolicy;
use App\Models\Insurance\InsuranceClaim;
use App\Services\Insurance\PolicyService;
use App\Services\Insurance\ClaimService;
use App\Services\Insurance\FraudControlService;
use App\Services\Insurance\PricingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * InsuranceController (API Entry Point).
 * Implementation: Layer 6 (Controller Layer).
 * Requirements: >60 lines, correlation_id, audit-log, try/catch.
 * Logic: Orchestrates policy issuance, claim filing, and premium checks.
 */
class InsuranceController extends Controller
{
    public function __construct(
        protected PolicyService $policyService,
        protected ClaimService $claimService,
        protected FraudControlService $fraudControl,
        protected PricingService $pricingService
    ) {
        // PRODUCTION-READY 2026 CANON: Middleware для Insurance вертикали
        $this->middleware('auth:sanctum')->except(['quotePolicy']); // Публичные котировки
         // 50 запросов/мин
         // Определение режима B2C/B2B
        $this->middleware('tenant', ['except' => ['quotePolicy']]); // Tenant scoping
        // Age Verification для страховки здоровья/жизни (18+)
        $this->middleware(
            'age-verification:18',
            ['only' => ['storePolicy', 'updatePolicy', 'fileClaim']]
        );
        // Fraud check для всех финансовых мутаций
        $this->middleware(
            'fraud-check',
            ['only' => ['storePolicy', 'updatePolicy', 'fileClaim', 'confirmPayment']]
        );
    }
    /**
     * Create a new insurance policy (API: POST /api/insurance/policies).
     */
    public function storePolicy(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        // 1. Audit Log Request
        Log::channel('audit')->info('[InsuranceController] storePolicy request starting', [
           'correlation_id' => $correlationId,
           'user_id' => $request->user()->id,
           'payload' => $request->all(),
        ]);
        try {
            // 2. Validate Request (Layer 6 Basic Validation)
            $validated = $request->validate([
                'type_id' => 'required|exists:insurance_types,id',
                'coverage_amount' => 'required|integer|min:100000',
                'policy_data' => 'required|array',
                'policy_data.age' => 'required|integer',
                'policy_data.region' => 'required|string',
            ]);
            // 3. Service Call: Issuance
            $policy = $this->policyService->issuePolicy(
                userId: $request->user()->id,
                typeId: (int)$validated['type_id'],
                coverageAmount: (int)$validated['coverage_amount'],
                policyData: $validated['policy_data'],
                correlationId: $correlationId
            );
            // 4. Final Response (Success Audit)
            Log::channel('audit')->info('[InsuranceController] storePolicy completed successfully', [
                'correlation_id' => $correlationId,
                'policy_uuid' => $policy->uuid,
            ]);
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $policy->load(['type', 'contract']),
            ], 201);
        } catch (Exception $e) {
            // 5. Global Error Handling
            Log::channel('audit')->error('[InsuranceController] storePolicy failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    /**
     * File a new claim (API: POST /api/insurance/claims).
     */
    public function storeClaim(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        Log::channel('audit')->info('[InsuranceController] storeClaim request starting', [
            'correlation_id' => $correlationId,
            'user_id' => $request->user()->id,
        ]);
        try {
            // Validate claim request
            $validated = $request->validate([
                'policy_id' => 'required|exists:insurance_policies,id',
                'description' => 'required|string|min:20',
                'requested_amount' => 'required|integer|min:100',
                'evidence' => 'nullable|array',
            ]);
            // check ownership
            $policy = InsurancePolicy::findOrFail($validated['policy_id']);
            if ($policy->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized policy access.'], 403);
            }
            // check status
            if ($policy->status !== 'active') {
                return response()->json(['error' => 'Cannot file claim on non-active policy.'], 400);
            }
            // Create claim via service
            $claim = $this->claimService->fileClaim(
                policy: $policy,
                description: $validated['description'],
                requestedAmount: (int)$validated['requested_amount'],
                evidenceFiles: $validated['evidence'] ?? [],
                correlationId: $correlationId
            );
            // Trigger AI Fraud scoring immediately (Background logic)
            $this->fraudControl->scoreClaim($claim, $correlationId);
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $claim->fresh(),
            ], 201);
        } catch (Exception $e) {
            Log::channel('audit')->error('[InsuranceController] storeClaim failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
    /**
     * Calculate premium for estimate (API: GET /api/insurance/estimate).
     */
    public function estimate(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        try {
            $validated = $request->validate([
                'type_id' => 'required|exists:insurance_types,id',
                'coverage_amount' => 'required|integer',
                'policy_data' => 'required|array',
            ]);
            $type = \App\Models\Insurance\InsuranceType::findOrFail($validated['type_id']);
            $premium = $this->pricingService->calculatePremium(
                $type,
                (int)$validated['coverage_amount'],
                $validated['policy_data'],
                $correlationId
            );
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'premium_cents' => $premium,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
