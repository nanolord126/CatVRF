<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\Review;
use App\Domains\Beauty\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ReviewController
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    public function index(int $serviceId): JsonResponse
    {
        try {
            $reviews = Review::where('service_id', $serviceId)
                ->where('status', 'approved')
                ->with('user')
                ->paginate(20);

            $correlationId = Str::uuid();
            Log::channel('audit')->info('Beauty reviews listed', [
                'service_id' => $serviceId,
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
            Log::error('Beauty review listing failed', [
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

    public function store(int $serviceId): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();

            $review = DB::transaction(function () use ($serviceId, $correlationId) {
                return Review::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'service_id' => $serviceId,
                    'user_id' => auth()->id(),
                    'appointment_id' => request('appointment_id'),
                    'rating' => request('rating'),
                    'title' => request('title'),
                    'comment' => request('comment'),
                    'images' => request('images', []),
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Beauty review created', [
                'review_id' => $review->id,
                'service_id' => $serviceId,
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
            Log::error('Beauty review creation failed', [
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

    public function destroy(int $serviceId, int $reviewId): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();
            $review = Review::findOrFail($reviewId);

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

            Log::channel('audit')->info('Beauty review deleted', [
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
            Log::error('Beauty review deletion failed', [
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
