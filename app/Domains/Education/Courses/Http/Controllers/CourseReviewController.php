<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourseReviewController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function indexByCourse(int $id): JsonResponse
        {
            try {
                $reviews = CourseReview::where('course_id', $id)
                    ->where('published_at', '!=', null)
                    ->with(['student'])
                    ->orderByDesc('published_at')
                    ->paginate(10);

                return response()->json([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                \Log::channel('audit')->error('Failed to list course reviews', [
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to list course reviews',
                ], 500);
            }
        }

        public function store(int $courseId): JsonResponse
        {
            $fraudResult = $this->fraudControlService->check(
                auth()->id() ?? 0,
                'operation',
                0,
                request()->ip(),
                request()->header('X-Device-Fingerprint'),
                $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $course = Course::findOrFail($courseId);
                $enrollment = Enrollment::where('course_id', $courseId)
                    ->where('student_id', auth()->id())
                    ->firstOrFail();

                $validated = request()->validate([
                    'rating' => 'required|integer|min:1|max:5',
                    'title' => 'required|string|max:255',
                    'content' => 'required|string|max:2000',
                ]);

                $correlationId = Str::uuid()->toString();

                $review = DB::transaction(function () use ($course, $enrollment, $validated, $courseId, $correlationId) {
                    $review = CourseReview::create([
                        'tenant_id' => tenant('id'),
                        'course_id' => $courseId,
                        'student_id' => auth()->id(),
                        'enrollment_id' => $enrollment->id,
                        'rating' => $validated['rating'],
                        'title' => $validated['title'],
                        'content' => $validated['content'],
                        'verified_purchase' => true,
                        'published_at' => now(),
                        'correlation_id' => $correlationId,
                    ]);

                    // Recalculate course rating
                    $avgRating = CourseReview::where('course_id', $courseId)->avg('rating');
                    $course->update([
                        'rating' => round($avgRating, 1),
                        'review_count' => $course->reviews()->count() + 1,
                    ]);

                    return $review;
                });

                \Log::channel('audit')->info('Review created', [
                    'review_id' => $review->id,
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

        public function myReviews(): JsonResponse
        {
            try {
                $reviews = CourseReview::where('student_id', auth()->id())
                    ->with(['course'])
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
                    'message' => 'Failed to list my reviews',
                ], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $fraudResult = $this->fraudControlService->check(
                auth()->id() ?? 0,
                'operation',
                0,
                request()->ip(),
                request()->header('X-Device-Fingerprint'),
                $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => auth()->id(),
                    'score'          => $fraudResult['score'],
                ]);
                return response()->json([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $review = CourseReview::findOrFail($id);
                $this->authorize('update', $review);

                $validated = request()->validate([
                    'rating' => 'sometimes|integer|min:1|max:5',
                    'title' => 'sometimes|string|max:255',
                    'content' => 'sometimes|string|max:2000',
                ]);

                $correlationId = Str::uuid()->toString();
                $review->update($validated + ['correlation_id' => $correlationId]);

                \Log::channel('audit')->info('Review updated', [
                    'review_id' => $review->id,
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
                $review = CourseReview::findOrFail($id);
                $this->authorize('delete', $review);

                $correlationId = Str::uuid()->toString();
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
