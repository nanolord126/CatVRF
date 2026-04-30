<?php

declare(strict_types=1);

namespace Modules\Recommendation\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Application\DTOs\RecommendationRequestDTO;
use Modules\Recommendation\Application\DTOs\RecommendationResponseDTO;
use Modules\Recommendation\Application\Services\ImpressionTrackingService;
use Modules\Recommendation\Application\Services\RecommendationFacade;
use Modules\Recommendation\Application\Services\RecommendationOrchestrator;
use Modules\Recommendation\Application\Services\SellerRecommendationService;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\Enums\RecommendationSource;
use Symfony\Component\HttpFoundation\Response;

final class RecommendationController extends Controller
{
    public function __construct(
        private readonly RecommendationOrchestrator $orchestrator,
        private readonly SellerRecommendationService $sellerService,
        private readonly ImpressionTrackingService $trackingService,
    ) {}

    public function getRecommendations(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'user_id' => 'required|integer',
                'scenario' => 'required|string|in:home_feed,product_detail,cart,search,seller_page,category_browse,checkout_upsell,email_digest,reorder',
                'item_id' => 'nullable|integer',
                'seller_id' => 'nullable|integer',
                'query' => 'nullable|string|max:255',
                'vertical' => 'nullable|string|max:100',
                'limit' => 'nullable|integer|min:1|max:100',
                'context' => 'nullable|array',
                'correlation_id' => 'required|string',
                'preferred_source' => 'nullable|string',
            ]);

            $correlationId = $validated['correlation_id'];
            $limit = (int) ($validated['limit'] ?? 20);

            $recommendationRequest = new RecommendationRequestDTO(
                tenantId: (int) $validated['tenant_id'],
                userId: (int) $validated['user_id'],
                scenario: RecommendationScenario::from($validated['scenario']),
                itemId: $validated['item_id'] ? (int) $validated['item_id'] : null,
                sellerId: $validated['seller_id'] ? (int) $validated['seller_id'] : null,
                query: $validated['query'] ?? null,
                vertical: $validated['vertical'] ?? null,
                limit: $limit,
                context: $validated['context'] ?? [],
                correlationId: $correlationId,
                preferredSource: $validated['preferred_source'] ? RecommendationSource::tryFrom($validated['preferred_source']) : null,
            );

            $response = $this->orchestrator->getRecommendations($recommendationRequest);

            return response()->json([
                'status' => 'success',
                'data' => $response->toArray(),
            ], Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid recommendation request', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request parameters: ' . $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            Log::error('Recommendation generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function trackImpression(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'user_id' => 'required|integer',
                'item_id' => 'required|integer',
                'position' => 'required|integer|min:0',
                'scenario' => 'required|string',
                'source' => 'required|string',
                'correlation_id' => 'required|string',
            ]);

            $this->trackingService->trackImpression(
                tenantId: (int) $validated['tenant_id'],
                userId: (int) $validated['user_id'],
                itemId: (int) $validated['item_id'],
                position: (int) $validated['position'],
                scenario: $validated['scenario'],
                source: $validated['source'],
                correlationId: $validated['correlation_id'],
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Impression tracked',
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Failed to track impression', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to track impression',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function trackClick(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'user_id' => 'required|integer',
                'item_id' => 'required|integer',
                'scenario' => 'required|string',
                'correlation_id' => 'required|string',
            ]);

            $this->trackingService->trackClick(
                tenantId: (int) $validated['tenant_id'],
                userId: (int) $validated['user_id'],
                itemId: (int) $validated['item_id'],
                scenario: $validated['scenario'],
                correlationId: $validated['correlation_id'],
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Click tracked',
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Failed to track click', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to track click',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function trackConversion(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'user_id' => 'required|integer',
                'item_id' => 'required|integer',
                'revenue' => 'required|numeric|min:0',
                'scenario' => 'required|string',
                'correlation_id' => 'required|string',
            ]);

            $this->trackingService->trackConversion(
                tenantId: (int) $validated['tenant_id'],
                userId: (int) $validated['user_id'],
                itemId: (int) $validated['item_id'],
                revenue: (float) $validated['revenue'],
                scenario: $validated['scenario'],
                correlationId: $validated['correlation_id'],
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Conversion tracked',
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Failed to track conversion', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to track conversion',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'scenario' => 'nullable|string',
                'hours' => 'nullable|integer|min:1|max:168',
            ]);

            $scenario = $validated['scenario'] ?? null;
            $hours = (int) ($validated['hours'] ?? 24);

            $metrics = $this->trackingService->getPerformanceMetrics(
                (int) $validated['tenant_id'],
                $scenario,
                $hours
            );

            return response()->json([
                'status' => 'success',
                'data' => $metrics,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Failed to get performance metrics', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get performance metrics',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function invalidateCache(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'user_id' => 'nullable|integer',
            ]);

            if (isset($validated['user_id'])) {
                $this->orchestrator->invalidateUserCache((int) $validated['user_id']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cache invalidated',
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Failed to invalidate cache', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to invalidate cache',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getModelHealth(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
            ]);

            $health = RecommendationFacade::forUser(0)->getModelHealth((int) $validated['tenant_id']);

            return response()->json([
                'status' => 'success',
                'data' => $health,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Failed to get model health', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get model health',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
