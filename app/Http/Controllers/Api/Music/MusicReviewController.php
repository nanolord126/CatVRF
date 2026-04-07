<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Music;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class MusicReviewController extends Controller
{

    /**
         * Create a new controller instance.
         */
        public function __construct(
            private readonly MusicBookingService $bookingService,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Display a listing of reviews for current tenant.
         */
        public function index(): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $reviews = $this->bookingService->listReviews(tenant()->id);
                return $this->response->json([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to list music reviews', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Не удалось получить отзывы.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Create a new review for an instrument, studio, or lesson.
         */
        public function store(MusicReviewRequest $request): JsonResponse
        {
            $correlationId = $request->input('correlation_id', (string) Str::uuid());
            try {
                $review = $this->bookingService->addReview(
                    $request->validated(),
                    tenant()->id,
                    $correlationId
                );
                $this->logger->channel('audit')->info('New music review submitted via API', [
                    'review_id' => $review->id,
                    'reviewable_type' => $review->reviewable_type,
                    'rating' => $review->rating,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to submit music review', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Ошибка при отправке отзыва: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * Show detailed information for a specific review.
         */
        public function show(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $review = $this->bookingService->getReviewWithDetails($id);
                return $this->response->json([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Отзыв не найден.',
                    'correlation_id' => $correlationId,
                ], 404);
            }
        }
        /**
         * Delete the specified review.
         */
        public function destroy(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $this->bookingService->deleteReview($id, $correlationId);
                $this->logger->channel('audit')->info('Music review deleted via API', [
                    'review_id' => $id,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => true,
                    'message' => 'Отзыв успешно удален.',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Ошибка при удалении отзыва.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Verify a review (internal tool for management).
         */
        public function verify(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $review = $this->bookingService->verifyReview($id, $correlationId);
                return $this->response->json([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Ошибка при верификации отзыва.',
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
}
