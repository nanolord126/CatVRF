<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Http\Controllers;

use App\Domains\FashionRetail\Models\FashionRetailReview;
use App\Domains\FashionRetail\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionRetailReviewController
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    public function index(int $productId): JsonResponse
    {
        try {
            $reviews = FashionRetailReview::where('product_id', $productId)
                ->where('status', 'approved')
                ->with('user')
                ->paginate(20);

            $correlationId = Str::uuid();
            Log::channel('audit')->info('FashionRetail reviews listed', [
                'product_id' => $productId,
                'count' => $reviews->count(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('FashionRetail review listing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function store(int $productId): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();

            $review = DB::transaction(function () use ($productId, $correlationId) {
                return FashionRetailReview::create([
                    'uuid' => Str::uuid(),
                    'product_id' => $productId,
                    'user_id' => auth()->id(),
                    'order_id' => request('order_id'),
                    'rating' => request('rating'),
                    'title' => request('title'),
                    'comment' => request('comment'),
                    'images' => request('images', []),
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('FashionRetail review created', [
                'review_id' => $review->id,
                'product_id' => $productId,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('FashionRetail review creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(int $productId, int $reviewId): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();
            $review = FashionRetailReview::findOrFail($reviewId);

            if ($review->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            DB::transaction(function () use ($review, $correlationId) {
                $review->update(['status' => 'deleted', 'correlation_id' => $correlationId]);
                $review->delete();
            });

            Log::channel('audit')->info('FashionRetail review deleted', [
                'review_id' => $reviewId,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('FashionRetail review deletion failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
