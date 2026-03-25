<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Models\FlowerReview;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class FlowerReviewController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function store(int $orderId, Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $order = FlowerOrder::query()->findOrFail($orderId);
            
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'quality_rating' => 'required|integer|min:1|max:5',
                'delivery_rating' => 'required|integer|min:1|max:5',
                'freshness_rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $review = $this->db->transaction(function () use ($order, $validated, $correlationId) {
                $overallRating = round(
                    ($validated['quality_rating'] + $validated['delivery_rating'] + $validated['freshness_rating']) / 3,
                    1
                );

                $review = FlowerReview::query()->create([
                    'tenant_id' => $order->tenant_id,
                    'order_id' => $order->id,
                    'shop_id' => $order->shop_id,
                    'user_id' => auth()->id(),
                    'quality_rating' => $validated['quality_rating'],
                    'delivery_rating' => $validated['delivery_rating'],
                    'freshness_rating' => $validated['freshness_rating'],
                    'overall_rating' => $overallRating,
                    'comment' => $validated['comment'] ?? null,
                    'verified_purchase' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Flower review created', [
                    'review_id' => $review->id,
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_CREATED);
        } catch (\Exception $exception) {
            $this->log->channel('audit')->error('Review creation failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function shopReviews(int $shopId): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $reviews = FlowerReview::query()
                ->where('shop_id', $shopId)
                ->where('status', 'approved')
                ->with('user')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $review = FlowerReview::query()->findOrFail($id);
            
            if ($review->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'quality_rating' => 'integer|min:1|max:5',
                'delivery_rating' => 'integer|min:1|max:5',
                'freshness_rating' => 'integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $review = $this->db->transaction(function () use ($review, $validated, $correlationId) {
                $review->update([...$validated, 'correlation_id' => $correlationId]);

                $this->log->channel('audit')->info('Flower review updated', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $review = FlowerReview::query()->findOrFail($id);
            
            if ($review->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_FORBIDDEN);
            }

            $this->db->transaction(function () use ($review, $correlationId) {
                $review->delete();

                $this->log->channel('audit')->info('Flower review deleted', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Review deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
