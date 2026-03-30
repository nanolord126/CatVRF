<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelReviewController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraudControlService->check(auth()->id() ?? 0, 'review_store', 0, $request->ip(), null, $correlationId);

            try {
                $request->validate([
                    'agency_id' => 'nullable|exists:travel_agencies,id',
                    'tour_id' => 'nullable|exists:travel_tours,id',
                    'booking_id' => 'nullable|exists:travel_bookings,id',
                    'rating' => 'required|integer|min:1|max:5',
                    'comment' => 'required|string|min:10',
                    'review_aspects' => 'nullable|array',
                ]);

                $validated = $request->all();
                $review = DB::transaction(function () use ($validated, $correlationId) {
                    return TravelReview::create([
                        'tenant_id' => tenant()->id,
                        'agency_id' => ($validated['agency_id'] ?? null),
                        'tour_id' => ($validated['tour_id'] ?? null),
                        'reviewer_id' => auth()->id(),
                        'booking_id' => ($validated['booking_id'] ?? null),
                        'rating' => ($validated['rating'] ?? null),
                        'comment' => ($validated['comment'] ?? null),
                        'review_aspects' => ($validated['review_aspects'] ?? []),
                        'verified_booking' => ($validated['booking_id'] ?? null) ? true : false,
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                        'uuid' => Str::uuid()->toString(),
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
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraudControlService->check(auth()->id() ?? 0, 'review_update', 0, $request->ip(), null, $correlationId);

            try {
                $review = TravelReview::where('tenant_id', tenant()->id)
                    ->where('reviewer_id', auth()->id())
                    ->findOrFail($id);

                $validated = $request->all();
                $review = DB::transaction(function () use ($validated, $review, $correlationId) {
                    $review->update([
                        'rating' => ($validated['rating'] ?? $review->rating),
                        'comment' => ($validated['comment'] ?? $review->comment),
                        'review_aspects' => ($validated['review_aspects'] ?? $review->review_aspects),
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
            $correlationId = (string) Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'review_destroy', 0, request()->ip(), null, $correlationId);

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
                    'correlation_id' => Str::uuid()->toString(),
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get reviews',
                    'correlation_id' => Str::uuid()->toString(),
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
