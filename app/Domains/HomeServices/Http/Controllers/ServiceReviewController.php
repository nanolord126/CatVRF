<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;

use App\Domains\HomeServices\Models\ServiceReview;
use App\Domains\HomeServices\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ServiceReviewController
{
    public function __construct(private ReviewService $reviewService) {}

    public function byContractor(int $contractorId): JsonResponse
    {
        try {
            $reviews = ServiceReview::where('contractor_id', $contractorId)
                ->whereNotNull('published_at')
                ->with(['reviewer', 'job'])
                ->paginate(10);

            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch reviews'], 500);
        }
    }

    public function byListing(int $listingId): JsonResponse
    {
        try {
            $reviews = ServiceReview::where('service_listing_id', $listingId)
                ->whereNotNull('published_at')
                ->with(['reviewer'])
                ->paginate(10);

            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch reviews'], 500);
        }
    }

    public function myReviews(): JsonResponse
    {
        try {
            $reviews = ServiceReview::where('reviewer_id', auth()->id())
                ->with(['contractor', 'job'])
                ->paginate(10);

            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch reviews'], 500);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $validated = request()->validate([
                'contractor_id' => 'required|integer|exists:contractors,id',
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'job_id' => 'nullable|integer|exists:service_jobs,id',
            ]);

            $correlationId = Str::uuid();

            $review = \DB::transaction(fn() => $this->reviewService->createReview(
                $validated['contractor_id'],
                auth()->id(),
                $validated['rating'],
                $validated['title'],
                $validated['content'],
                $validated['job_id'] ?? null,
                $correlationId
            ));

            return response()->json(['success' => true, 'data' => $review, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to submit review'], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $review = ServiceReview::findOrFail($id);
            $this->authorize('update', $review);

            $validated = request()->validate([
                'rating' => 'sometimes|integer|min:1|max:5',
                'title' => 'sometimes|string',
                'content' => 'sometimes|string',
            ]);

            $correlationId = Str::uuid();

            $review = \DB::transaction(fn() => $this->reviewService->updateReview(
                $review,
                $validated['rating'] ?? $review->rating,
                $validated['title'] ?? $review->title,
                $validated['content'] ?? $review->content,
                $correlationId
            ));

            return response()->json(['success' => true, 'data' => $review, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update review'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $review = ServiceReview::findOrFail($id);
            $this->authorize('delete', $review);

            $review->delete();

            return response()->json(['success' => true, 'message' => 'Review deleted', 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete review'], 500);
        }
    }
}
