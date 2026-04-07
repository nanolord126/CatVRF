<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class InsuranceApiController extends Controller
{

    public function __construct(
            private readonly PolicyService $policyService,
            private readonly ClaimService $claimService,
            private readonly FraudControlService $fraud,
            private readonly PricingService $pricingService,
            private readonly AIRiskAssessmentService $aiService,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Get AI-Powered Recommendations for Insurance (GET /api/insurance/recommendations).
         */
        public function getRecommendations(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $this->logger->channel('audit')->info('[InsuranceAPI] Requesting AI recommendations', [
                    'correlation_id' => $correlationId,
                    'user_id' => $request->user()?->id,
                ]);
                $recommendations = $this->aiService->getRecommendations(
                    $request->user(),
                    $correlationId
                );
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $recommendations,
                ]);
            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

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
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'estimated_premium_cents' => $premium,
                ]);
            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

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
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $policy->load(['type', 'contract']),
                ], 201);
            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

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
                    return $this->response->json(['error' => 'Forbidden Access to Policy'], 403);
                }
                $claim = $this->claimService->fileClaim(
                    policy: $policy,
                    description: $validated['description'],
                    requestedAmount: (int)$validated['requested_amount'],
                    evidenceFiles: $validated['evidence'] ?? [],
                    correlationId: $correlationId
                );
                // Background Fraud Check
                $this->fraud->scoreClaim($claim, $correlationId);
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $claim->fresh(),
                ], 201);
            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                return $this->errorResponse($e, $correlationId);
            }
        }
        /**
         * Private Error Handling Wrapper.
         */
        private function errorResponse(Exception $e, string $correlationId): JsonResponse
        {
            $this->logger->channel('audit')->error('[InsuranceAPI] Operation Failed', [
                'correlation_id' => $correlationId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->response->json([
                'success' => false,
                'correlation_id' => $correlationId,
                'message' => $e->getMessage(),
                'type' => class_basename($e),
            ], 400);
        }
}
