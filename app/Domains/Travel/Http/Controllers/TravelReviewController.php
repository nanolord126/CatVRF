<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Models\TravelReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TravelReviewController
{
    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $correlationId = $request->get('correlation_id', Str::uuid()->toString());

        try {
            $request->validate([
                'agency_id' => 'nullable|exists:travel_agencies,id',
                'tour_id' => 'nullable|exists:travel_tours,id',
                'booking_id' => 'nullable|exists:travel_bookings,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|min:10',
                'review_aspects' => 'nullable|array',
            ]);

            $review = DB::transaction(function () use ($request, $correlationId) {
                return TravelReview::create([
                    'tenant_id' => tenant()->id,
                    'agency_id' => $request->get('agency_id'),
                    'tour_id' => $request->get('tour_id'),
                    'reviewer_id' => auth()->id(),
                    'booking_id' => $request->get('booking_id'),
                    'rating' => $request->get('rating'),
                    'comment' => $request->get('comment'),
                    'review_aspects' => $request->get('review_aspects', []),
                    'verified_booking' => $request->get('booking_id') ? true : false,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);
            });

            Log::channel('audit')->info('Review created', [
                'review_id' => $review->id,
                'reviewer_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create review',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $correlationId = $request->get('correlation_id', Str::uuid()->toString());

        try {
            $review = TravelReview::where('tenant_id', tenant()->id)
                ->where('reviewer_id', auth()->id())
                ->findOrFail($id);

            $review = DB::transaction(function () use ($request, $review, $correlationId) {
                $review->update([
                    'rating' => $request->get('rating', $review->rating),
                    'comment' => $request->get('comment', $review->comment),
                    'review_aspects' => $request->get('review_aspects', $review->review_aspects),
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });

            Log::channel('audit')->info('Review updated', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $correlationId = Str::uuid()->toString();

        try {
            $review = TravelReview::where('tenant_id', tenant()->id)
                ->where('reviewer_id', auth()->id())
                ->findOrFail($id);

            DB::transaction(function () use ($review) {
                $review->delete();
            });

            Log::channel('audit')->info('Review deleted', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function userReviews(): JsonResponse
    {
        try {
            $reviews = TravelReview::where('reviewer_id', auth()->id())
                ->where('tenant_id', tenant()->id)
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $reviews->items(),
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reviews',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function approve(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $review = TravelReview::where('tenant_id', tenant()->id)->findOrFail($id);

            DB::transaction(function () use ($review, $correlationId) {
                $review->update([
                    'status' => 'approved',
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Review approved', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve review',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function rejectReview(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $review = TravelReview::where('tenant_id', tenant()->id)->findOrFail($id);

            DB::transaction(function () use ($review, $correlationId) {
                $review->update([
                    'status' => 'rejected',
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Review rejected', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject review',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
