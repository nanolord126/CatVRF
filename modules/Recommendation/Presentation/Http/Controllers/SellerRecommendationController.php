<?php

declare(strict_types=1);

namespace Modules\Recommendation\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Application\Services\SellerRecommendationService;
use Symfony\Component\HttpFoundation\Response;

final class SellerRecommendationController extends Controller
{
    public function __construct(
        private readonly SellerRecommendationService $sellerService,
    ) {}

    public function getPromotions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'seller_id' => 'required|integer',
                'limit' => 'nullable|integer|min:1|max:50',
            ]);

            $limit = (int) ($validated['limit'] ?? 12);
            $recommendations = $this->sellerService->suggestProductsToPromote(
                (int) $validated['tenant_id'],
                (int) $validated['seller_id'],
                $limit,
            );

            return response()->json([
                'status' => 'success',
                'data' => $recommendations,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Failed to get seller promotions', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotions',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getMetrics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'seller_id' => 'required|integer',
            ]);

            $metrics = $this->sellerService->getSellerPerformanceMetrics(
                (int) $validated['tenant_id'],
                (int) $validated['seller_id'],
            );

            return response()->json([
                'status' => 'success',
                'data' => $metrics,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Failed to get seller metrics', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get metrics',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
