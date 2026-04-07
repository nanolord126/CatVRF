<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Tickets\Models\Event;
use App\Domains\Tickets\Models\EventReview;
use App\Domains\Tickets\Services\EventReviewService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class EventReviewController
{
    public function __construct(private readonly EventReviewService $reviewService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function byEvent(int $eventId): JsonResponse
        {
            try {
                $reviews = EventReview::where('event_id', $eventId)
                    ->where('published_at', '!=', null)
                    ->with(['buyer', 'event'])
                    ->orderBy('published_at', 'desc')
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => Str::uuid()->toString(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list reviews', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list reviews',
                ], 500);
            }
        }

        public function myReviews(): JsonResponse
        {
            try {
                $reviews = EventReview::where('buyer_id', $request->user()?->id)
                    ->with(['event'])
                    ->orderBy('published_at', 'desc')
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => Str::uuid()->toString(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list my reviews', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list reviews',
                ], 500);
            }
        }

        public function store(int $eventId): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'review_store', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->authorize('create', EventReview::class);

                $validated = $request->validate([
                    'rating' => 'required|integer|min:1|max:5',
                    'title' => 'required|string|max:255',
                    'content' => 'required|string|max:2000',
                    'categories' => 'nullable|array',
                ]);

                $review = $this->db->transaction(function () use ($eventId, $validated, $correlationId) {
                    return $this->reviewService->createReview(
                        $eventId,
                        $request->user()?->id,
                        $validated['rating'],
                        $validated['title'],
                        $validated['content'],
                        $validated['categories'] ?? [],
                        $correlationId
                    );
                });

                $this->logger->info('Review created', [
                    'event_id' => $eventId,
                    'rating' => $validated['rating'],
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create review', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create review',
                ], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'review_update', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = EventReview::findOrFail($id);
                $this->authorize('update', $review);

                $validated = $request->validate([
                    'rating' => 'sometimes|integer|min:1|max:5',
                    'title' => 'sometimes|string|max:255',
                    'content' => 'sometimes|string|max:2000',
                ]);

                $review->update($validated + ['correlation_id' => $correlationId]);

                $this->logger->info('Review updated', [
                    'review_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update review', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
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

                $correlationId = (string) Str::uuid()->toString();
                $review->delete();

                $this->logger->info('Review deleted', [
                    'review_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Review deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to delete review', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete review',
                ], 500);
            }
        }
}
