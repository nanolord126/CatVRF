<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class MedicalReviewController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function doctorReviews(int $doctorId): JsonResponse
        {
            try {
                $reviews = MedicalReview::where('doctor_id', $doctorId)
                    ->where('status', 'approved')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch reviews'], 500);
            }
        }

        public function store(Request $request, int $doctorId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $validated = $request->all();
                $review = $this->db->transaction(function () use ($validated, $doctorId) {
                    return MedicalReview::create([
                        'tenant_id' => $request->user()->tenant_id,
                        'doctor_id' => $doctorId,
                        'reviewer_id' => $request->user()->id,
                        'appointment_id' => ($validated['appointment_id'] ?? null),
                        'rating' => ($validated['rating'] ?? null),
                        'comment' => ($validated['comment'] ?? null),
                        'review_aspects' => ($validated['review_aspects'] ?? null),
                        'verified_appointment' => !!($validated['appointment_id'] ?? null),
                        'status' => 'pending',
                        'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                    ]);
                });

                $this->logger->info('Review created', ['review_id' => $review->id]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 201);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to create review'], 500);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = MedicalReview::findOrFail($id);
                $this->authorize('update', $review);

                $review->update([
                    'comment' => $request->input('comment', $review->comment),
                    'rating' => $request->input('rating', $review->rating),
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);

                $this->logger->info('Review updated', ['review_id' => $review->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Update failed'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $review = MedicalReview::findOrFail($id);
                $this->authorize('delete', $review);

                $review->delete();

                $this->logger->info('Review deleted', ['review_id' => $review->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Delete failed'], 500);
            }
        }

        public function markHelpful(int $id): JsonResponse
        {
            try {
                $review = MedicalReview::findOrFail($id);

                $review->increment('helpful_count');

                $this->logger->info('Review marked helpful', ['review_id' => $review->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Operation failed'], 500);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $reviews = MedicalReview::paginate(50);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch reviews'], 500);
            }
        }

        public function approve(int $id): JsonResponse
        {
            try {
                $review = MedicalReview::findOrFail($id);

                $review->update(['status' => 'approved']);

                $this->logger->info('Review approved', ['review_id' => $review->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Approval failed'], 500);
            }
        }

        public function reject(int $id): JsonResponse
        {
            try {
                $review = MedicalReview::findOrFail($id);

                $review->delete();

                $this->logger->info('Review rejected', ['review_id' => $review->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Rejection failed'], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $reviews = MedicalReview::where('status', 'approved')->get();

                $analytics = [
                    'total_reviews' => $reviews->count(),
                    'average_rating' => $reviews->avg('rating'),
                    'by_rating' => $reviews->groupBy('rating')->map->count(),
                    'helpful_count' => $reviews->sum('helpful_count'),
                ];

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $analytics,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Analytics failed'], 500);
            }
        }
}
