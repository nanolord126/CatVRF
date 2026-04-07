<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class TravelReviewController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'review_store', amount: 0, correlationId: $correlationId ?? '');

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
                $review = $this->db->transaction(function () use ($validated, $correlationId) {
                    return TravelReview::create([
                        'tenant_id' => tenant()->id,
                        'agency_id' => ($validated['agency_id'] ?? null),
                        'tour_id' => ($validated['tour_id'] ?? null),
                        'reviewer_id' => $request->user()?->id,
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

                $this->logger->info('Review created', [
                    'review_id' => $review->id,
                    'reviewer_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create review',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'review_update', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = TravelReview::where('tenant_id', tenant()->id)
                    ->where('reviewer_id', $request->user()?->id)
                    ->findOrFail($id);

                $validated = $request->all();
                $review = $this->db->transaction(function () use ($validated, $review, $correlationId) {
                    $review->update([
                        'rating' => ($validated['rating'] ?? $review->rating),
                        'comment' => ($validated['comment'] ?? $review->comment),
                        'review_aspects' => ($validated['review_aspects'] ?? $review->review_aspects),
                        'correlation_id' => $correlationId,
                    ]);

                    return $review;
                });

                $this->logger->info('Review updated', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update review',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'review_destroy', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = TravelReview::where('tenant_id', tenant()->id)
                    ->where('reviewer_id', $request->user()?->id)
                    ->findOrFail($id);

                $this->db->transaction(function () use ($review) {
                    $review->delete();
                });

                $this->logger->info('Review deleted', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete review',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function userReviews(): JsonResponse
        {
            try {
                $reviews = TravelReview::where('reviewer_id', $request->user()?->id)
                    ->where('tenant_id', tenant()->id)
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews->items(),
                    'correlation_id' => Str::uuid()->toString(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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

                $this->db->transaction(function () use ($review, $correlationId) {
                    $review->update([
                        'status' => 'approved',
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('Review approved', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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

                $this->db->transaction(function () use ($review, $correlationId) {
                    $review->update([
                        'status' => 'rejected',
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('Review rejected', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to reject review',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
