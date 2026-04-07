<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ReviewController extends Controller
{

    public function __construct(private ReviewService $reviewService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function byStudio(int $studioId): JsonResponse
        {
            try {
                $reviews = Review::where('studio_id', $studioId)
                    ->where('published_at', '!=', null)
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to list reviews'], 500);
            }
        }

        public function myReviews(): JsonResponse
        {
            try {
                $reviews = Review::where('reviewer_id', $request->user()?->id)->paginate(10);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to list reviews'], 500);
            }
        }

        public function store(int $studioId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $validated = $request->validate([
                    'rating' => 'required|integer|min:1|max:5',
                    'title' => 'required|string|max:255',
                    'content' => 'required|string',
                ]);

                $correlationId = Str::uuid()->toString();

                $review = $this->db->transaction(fn() => $this->reviewService->createReview(
                    $studioId,
                    null,
                    $request->user()?->id,
                    $validated['rating'],
                    $validated['title'],
                    $validated['content'],
                    [],
                    true,
                    null,
                    $correlationId
                ));

                $this->logger->info('Sports studio review created', [
                    'correlation_id' => $correlationId,
                    'review_id'      => $review->id ?? null,
                    'studio_id'      => $studioId,
                    'user_id'        => $request->user()?->id,
                    'rating'         => $validated['rating'],
                ]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to create review'], 500);
            }
        }

        public function storeForTrainer(int $trainerId): JsonResponse
        {
            try {
                $validated = $request->validate([
                    'rating' => 'required|integer|min:1|max:5',
                    'title' => 'required|string|max:255',
                    'content' => 'required|string',
                ]);

                $correlationId = Str::uuid()->toString();

                $review = $this->db->transaction(fn() => $this->reviewService->createReview(
                    null,
                    $trainerId,
                    $request->user()?->id,
                    $validated['rating'],
                    $validated['title'],
                    $validated['content'],
                    [],
                    true,
                    null,
                    $correlationId
                ));

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to create review'], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = Review::findOrFail($id);
                $this->authorize('update', $review);

                $validated = $request->validate([
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

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to update review'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $review = Review::findOrFail($id);
                $this->authorize('delete', $review);

                $correlationId = Str::uuid()->toString();
                $review->delete();

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Review deleted', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to delete review'], 500);
            }
        }
}
