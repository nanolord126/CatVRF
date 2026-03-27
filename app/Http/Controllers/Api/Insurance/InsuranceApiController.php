<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Insurance;
use App\Http\Controllers\Controller;
use App\Models\Insurance\InsurancePolicy;
use App\Models\Insurance\InsuranceClaim;
use App\Services\Insurance\PolicyService;
use App\Services\Insurance\ClaimService;
use App\Services\Insurance\FraudControlService;
use App\Services\Insurance\PricingService;
use App\Services\Insurance\AIRiskAssessmentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * InsuranceApiController (Layer 6: Controller).
 * Implementation: Canonical 2026 for Insurance Vertical.
 * Logic: Policy creation, AI assessment, and Claims management.
 * Requirements: >60 lines, correlation_id, full audit.
 */
final class InsuranceApiController extends Controller
{
    public function __construct(
        private readonly PolicyService $policyService,
        private readonly ClaimService $claimService,
        private readonly FraudControlService $fraudControl,
        private readonly PricingService $pricingService,
        private readonly AIRiskAssessmentService $aiService
    ) {}
    /**
     * Get AI-Powered Recommendations for Insurance (GET /api/insurance/recommendations).
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        try {
            Log::channel('audit')->info('[InsuranceAPI] Requesting AI recommendations', [
                'correlation_id' => $correlationId,
                'user_id' => $request->user()?->id,
            ]);
            $recommendations = $this->aiService->getRecommendations(
                $request->user(),
                $correlationId
            );
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $recommendations,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    /**
     * Calculate Insurance Premium (POST /api/insurance/calculate).
     */
    public function calculate(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        try {
            $validated = $request->validate([
                'type_slug' => ['required', 'string', 'exists:insurance_types,slug'],
                'coverage_amount' => ['required', 'integer', 'min:100000'],
                'params' => ['required', 'array'],
                'is_b2b' => ['nullable', 'boolean'],
            ]);
            $type = \App\Models\Insurance\InsuranceType::where('slug', $validated['type_slug'])->firstOrFail();
            $premium = $this->pricingService->calculatePremium(
                $type,
                $request->user(),
                $validated['params'],
                $validated['is_b2b'] ?? false,
                $correlationId
            );
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'estimated_premium_cents' => $premium,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    /**
     * Issue a new policy (POST /api/insurance/policies).
     */
    public function storePolicy(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        try {
            $validated = $request->validate([
                'type_id' => ['required', 'integer', 'exists:insurance_types,id'],
                'coverage_amount' => ['required', 'integer', 'min:100000'],
                'policy_data' => ['required', 'array'],
                'is_b2b' => ['nullable', 'boolean'],
            ]);
            $policy = $this->policyService->issuePolicy(
                userId: $request->user()->id,
                typeId: (int)$validated['type_id'],
                coverageAmount: (int)$validated['coverage_amount'],
                policyData: $validated['policy_data'],
                isB2B: $validated['is_b2b'] ?? false,
                correlationId: $correlationId
            );
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $policy->load(['type', 'contract']),
            ], 201);
        } catch (Exception $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    /**
     * File a claim (POST /api/insurance/claims).
     */
    public function storeClaim(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        try {
            $validated = $request->validate([
                'policy_id' => ['required', 'integer', 'exists:insurance_policies,id'],
                'description' => ['required', 'string', 'min:30'],
                'requested_amount' => ['required', 'integer', 'min:100'],
                'evidence' => ['nullable', 'array'],
            ]);
            $policy = \App\Models\Insurance\InsurancePolicy::findOrFail($validated['policy_id']);
            // Authorization Check
            if ($policy->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Forbidden Access to Policy'], 403);
            }
            $claim = $this->claimService->fileClaim(
                policy: $policy,
                description: $validated['description'],
                requestedAmount: (int)$validated['requested_amount'],
                evidenceFiles: $validated['evidence'] ?? [],
                correlationId: $correlationId
            );
            // Background Fraud Check
            $this->fraudControl->scoreClaim($claim, $correlationId);
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $claim->fresh(),
            ], 201);
        } catch (Exception $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    /**
     * Private Error Handling Wrapper.
     */
    private function errorResponse(Exception $e, string $correlationId): JsonResponse
    {
        Log::channel('audit')->error('[InsuranceAPI] Operation Failed', [
            'correlation_id' => $correlationId,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'correlation_id' => $correlationId,
            'message' => $e->getMessage(),
            'type' => class_basename($e),
        ], 400);
    }
}
