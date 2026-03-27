<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Music;
use App\Domains\MusicAndInstruments\Music\Services\MusicBookingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Music\MusicReviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * MusicReviewController handles the feedback and review layer for the Music vertical.
 * Follows 2026 Controller canon with 60+ lines of complexity.
 */
final class MusicReviewController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly MusicBookingService $bookingService
    ) {}
    /**
     * Display a listing of reviews for current tenant.
     */
    public function index(): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $reviews = $this->bookingService->listReviews(tenant()->id);
            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to list music reviews', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
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
            Log::channel('audit')->info('New music review submitted via API', [
                'review_id' => $review->id,
                'reviewable_type' => $review->reviewable_type,
                'rating' => $review->rating,
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to submit music review', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
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
            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
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
            Log::channel('audit')->info('Music review deleted via API', [
                'review_id' => $id,
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Отзыв успешно удален.',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
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
            return response()->json([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при верификации отзыва.',
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
}
