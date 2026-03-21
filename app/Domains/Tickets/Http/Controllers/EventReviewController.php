<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Tickets\Models\{EventReview, Event};
use App\Domains\Tickets\Services\EventReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class EventReviewController
{
    public function __construct(private EventReviewService $reviewService) {}

    public function byEvent(int $eventId): JsonResponse
    {
        try {
            $reviews = EventReview::where('event_id', $eventId)
                ->where('published_at', '!=', null)
                ->with(['buyer', 'event'])
                ->orderBy('published_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list reviews', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to list reviews',
            ], 500);
        }
    }

    public function myReviews(): JsonResponse
    {
        try {
            $reviews = EventReview::where('buyer_id', auth()->id())
                ->with(['event'])
                ->orderBy('published_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list my reviews', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to list reviews',
            ], 500);
        }
    }

    public function store(int $eventId): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $this->authorize('create', EventReview::class);

            $validated = request()->validate([
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'required|string|max:255',
                'content' => 'required|string|max:2000',
                'categories' => 'nullable|array',
            ]);

            $correlationId = Str::uuid();

            $review = DB::transaction(function () use ($eventId, $validated, $correlationId) {
                return $this->reviewService->createReview(
                    $eventId,
                    auth()->id(),
                    $validated['rating'],
                    $validated['title'],
                    $validated['content'],
                    $validated['categories'] ?? [],
                    $correlationId
                );
            });

            \Log::channel('audit')->info('Review created', [
                'event_id' => $eventId,
                'rating' => $validated['rating'],
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to create review', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create review',
            ], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $review = EventReview::findOrFail($id);
            $this->authorize('update', $review);

            $validated = request()->validate([
                'rating' => 'sometimes|integer|min:1|max:5',
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string|max:2000',
            ]);

            $correlationId = Str::uuid();
            $review->update($validated + ['correlation_id' => $correlationId]);

            \Log::channel('audit')->info('Review updated', [
                'review_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to update review', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review',
            ], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $review = EventReview::findOrFail($id);
            $this->authorize('delete', $review);

            $correlationId = Str::uuid();
            $review->delete();

            \Log::channel('audit')->info('Review deleted', [
                'review_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to delete review', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review',
            ], 500);
        }
    }
}
