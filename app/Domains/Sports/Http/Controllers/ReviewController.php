<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Models\Review;
use App\Domains\Sports\Services\ReviewService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class ReviewController
{
    public function __construct(
        private ReviewService $reviewService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function byStudio(int $studioId): JsonResponse
    {
        try {
            $reviews = Review::where('studio_id', $studioId)
                ->where('published_at', '!=', null)
                ->paginate(10);

            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list reviews'], 500);
        }
    }

    public function myReviews(): JsonResponse
    {
        try {
            $reviews = Review::where('reviewer_id', auth()->id())->paginate(10);
            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list reviews'], 500);
        }
    }

    public function store(int $studioId): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $validated = request()->validate([
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            $correlationId = Str::uuid()->toString();

            $review = $this->db->transaction(fn() => $this->reviewService->createReview(
                $studioId,
                null,
                auth()->id(),
                $validated['rating'],
                $validated['title'],
                $validated['content'],
                [],
                true,
                null,
                $correlationId
            ));

            $this->log->channel('audit')->info('Sports studio review created', [
                'correlation_id' => $correlationId,
                'review_id'      => $review->id ?? null,
                'studio_id'      => $studioId,
                'user_id'        => auth()->id(),
                'rating'         => $validated['rating'],
            ]);

            return response()->json(['success' => true, 'data' => $review, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create review'], 500);
        }
    }

    public function storeForTrainer(int $trainerId): JsonResponse
    {
        try {
            $validated = request()->validate([
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            $correlationId = Str::uuid()->toString();

            $review = $this->db->transaction(fn() => $this->reviewService->createReview(
                null,
                $trainerId,
                auth()->id(),
                $validated['rating'],
                $validated['title'],
                $validated['content'],
                [],
                true,
                null,
                $correlationId
            ));

            return response()->json(['success' => true, 'data' => $review, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create review'], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $review = Review::findOrFail($id);
            $this->authorize('update', $review);

            $validated = request()->validate([
                'rating' => 'sometimes|integer|min:1|max:5',
                'title' => 'sometimes|string',
                'content' => 'sometimes|string',
            ]);
            $review = $this->reviewService->updateReview(
                $review,
                $validated['rating'] ?? $review->rating,
                $validated['title'] ?? $review->title,
                $validated['content'] ?? $review->content,
                [],
                $correlationId
            );

            return response()->json(['success' => true, 'data' => $review, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update review'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $review = Review::findOrFail($id);
            $this->authorize('delete', $review);

            $correlationId = Str::uuid()->toString();
            $review->delete();

            return response()->json(['success' => true, 'message' => 'Review deleted', 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete review'], 500);
        }
    }
}
