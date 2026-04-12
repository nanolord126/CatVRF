<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ServiceReviewController extends Controller
{


    public function __construct(private ReviewService $reviewService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function byContractor(int $contractorId): JsonResponse
        {
            try {
                $reviews = ServiceReview::where('contractor_id', $contractorId)
                    ->whereNotNull('published_at')
                    ->with(['reviewer', 'job'])
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to fetch reviews'], 500);
            }
        }

        public function byListing(int $listingId): JsonResponse
        {
            try {
                $reviews = ServiceReview::where('service_listing_id', $listingId)
                    ->whereNotNull('published_at')
                    ->with(['reviewer'])
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to fetch reviews'], 500);
            }
        }

        public function myReviews(): JsonResponse
        {
            try {
                $reviews = ServiceReview::where('reviewer_id', $request->user()?->id)
                    ->with(['contractor', 'job'])
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to fetch reviews'], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $validated = $request->validate([
                    'contractor_id' => 'required|integer|exists:contractors,id',
                    'rating' => 'required|integer|min:1|max:5',
                    'title' => 'required|string|max:255',
                    'content' => 'required|string',
                    'job_id' => 'nullable|integer|exists:service_jobs,id',
                ]);

                $review = $this->db->transaction(fn() => $this->reviewService->createReview(
                    $validated['contractor_id'],
                    $request->user()?->id,
                    $validated['rating'],
                    $validated['title'],
                    $validated['content'],
                    $validated['job_id'] ?? null,
                    $correlationId
                ));

                $this->logger->info('HomeService review created', [
                    'correlation_id' => $correlationId,
                    'review_id'      => $review->id ?? null,
                    'contractor_id'  => $validated['contractor_id'],
                    'user_id'        => $request->user()?->id,
                    'rating'         => $validated['rating'],
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to submit review'], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = ServiceReview::findOrFail($id);
                $this->authorize('update', $review);

                $validated = $request->validate([
                    'rating' => 'sometimes|integer|min:1|max:5',
                    'title' => 'sometimes|string',
                    'content' => 'sometimes|string',
                ]);

                $review = $this->db->transaction(fn() => $this->reviewService->updateReview(
                    $review,
                    $validated['rating'] ?? $review->rating,
                    $validated['title'] ?? $review->title,
                    $validated['content'] ?? $review->content,
                    $correlationId
                ));

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to update review'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $review = ServiceReview::findOrFail($id);
                $this->authorize('delete', $review);

                $review->delete();

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Review deleted', 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to delete review'], 500);
            }
        }
}
