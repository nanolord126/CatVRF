<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ReviewController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {

                $validated = $request->all();
                return $this->db->transaction(function () use ($validated, $correlationId) {
                    $review = FreelanceReview::create([
                        'tenant_id' => tenant()->id,
                        'contract_id' => ($validated['contract_id'] ?? null),
                        'reviewer_id' => $request->user()?->id,
                        'freelancer_id' => ($validated['freelancer_id'] ?? null),
                        'client_id' => ($validated['client_id'] ?? null),
                        'review_type' => ($validated['review_type'] ?? 'client_to_freelancer'),
                        'communication_rating' => ($validated['communication_rating'] ?? null),
                        'work_quality_rating' => ($validated['work_quality_rating'] ?? null),
                        'timeliness_rating' => ($validated['timeliness_rating'] ?? null),
                        'overall_rating' => ($validated['overall_rating'] ?? null),
                        'comment' => ($validated['comment'] ?? null),
                        'review_aspects' => ($validated['review_aspects'] ?? []),
                        'verified_contract' => true,
                        'would_hire_again' => ($validated['would_hire_again'] ?? null),
                        'status' => 'approved',
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Freelance review submitted', [
                        'review_id' => $review->id,
                        'contract_id' => ($validated['contract_id'] ?? null),
                        'overall_rating' => ($validated['overall_rating'] ?? null),
                        'correlation_id' => $correlationId,
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'success' => true,
                        'data' => $review,
                        'correlation_id' => $correlationId,
                    ], 201);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Error submitting review', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to submit review',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function freelancerReviews(int $id): JsonResponse
        {
            try {
                $reviews = FreelanceReview::where('freelancer_id', $id)
                    ->where('status', 'approved')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing freelancer reviews', [
                    'freelancer_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list reviews',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function contractReviews(int $id): JsonResponse
        {
            try {
                $reviews = FreelanceReview::where('contract_id', $id)->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing contract reviews', [
                    'contract_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list reviews',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function markHelpful(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $review = FreelanceReview::findOrFail($id);

                $review->increment('helpful_count');

                $this->logger->info('Review marked as helpful', [
                    'review_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error marking review as helpful', [
                    'review_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to mark review',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function markUnhelpful(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $review = FreelanceReview::findOrFail($id);

                $review->increment('unhelpful_count');

                $this->logger->info('Review marked as unhelpful', [
                    'review_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error marking review as unhelpful', [
                    'review_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to mark review',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
